<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;

class SpotifyController extends BaseController {
	
  public const SEPERATOR = '|';
  public const PRINCE_TIMEZONE = 'America/New_York';
  
  public function __construct() {

	}
  
  public function login(Request $request) {
    
    // return $this->ok('login not implemented');
    
    $client_state = $request->input('client_state');
    $show_dialog = $request->input('show_dialog') == 'false' ? 'false' : 'true';
    
    $scope = "streaming user-read-email user-read-private user-library-read user-library-modify user-read-playback-state user-modify-playback-state";$state = uniqid() . self::SEPERATOR . $client_state;
    $params = [
      'response_type' => 'code',
      'client_id' => config('spotify.spotify_client_id'),
      'scope' => $scope,
      'redirect_uri' => url('/api/v1/spotify/callback'),
      //'state' => $state,
      'show_dialog' => $show_dialog,
    ];
    
    $url = 'https://accounts.spotify.com/authorize/?' . http_build_query( $params );
    
    return $this->sendResponse([
      'url' => $url
    ]);
  }
  
  public function callback(Request $request) {
    
    // return $this->ok('callback not implemented');
    
    $code = $request->input('code') ?? null;
    $state = $request->input('state') ?? uniqid();
    if ( ! $code ) {
      return $this->sendError('spotify error: callback code and/or state not found');
    }
    
    $payload = [
      'code' => $code,
      'redirect_uri' => url('/api/v1/spotify/callback'),
      'grant_type' => 'authorization_code'
    ];

    try {

      $response = Http::withBasicAuth(config('spotify.spotify_client_id'), config('spotify.spotify_client_secret'))
        ->asForm()
        ->post( 'https://accounts.spotify.com/api/token', $payload );
        
    } catch ( \Throwable $e ) {
      return $this->sendError( 'spotify token request error: ' . $e->getMessage() );
    }
    
    $json = $response->body();
    $json = json_decode( $json );
    
    $error = $json->error ?? null;
    if ( $error ) {
      return $this->sendError( 'spotify error: ' . ($json->error_description ?? 'unknown error') );
    }
    
    // cache the access -and- refresh tokens for the given client state 
    
    $access_token = $json->access_token ?? null;
    if ( ! $access_token ) {
      return $this->sendError('spotify error: access token not found');
    }
    $refresh_token = $json->refresh_token ?? null;
    if ( ! $refresh_token ) {
      return $this->sendError('spotify error: refresh token not found');
    }
    
    $client_state = explode(self::SEPERATOR, $state);
    $client_state = $client_state[1] ?? uniqid();
    
    if ( $client_state != '' ) {
      DB::table('spotify_tokens')->where('client_state', $client_state)->delete();
      DB::table('spotify_tokens')->insert([
        'client_state' => $client_state,
        'access_token' => $json->access_token,
        'refresh_token' => $json->refresh_token,
        'scopes' => $json->scope,
        'ttl' => $json->expires_in,
        'expires_at' => Carbon::now(self::PRINCE_TIMEZONE)->addSeconds( $json->expires_in )->format('Y-m-d H:i:s'),
        'created_at' => Carbon::now(self::PRINCE_TIMEZONE)->format('Y-m-d H:i:s'),
        'updated_at' => Carbon::now(self::PRINCE_TIMEZONE)->format('Y-m-d H:i:s')
      ]);
      // TODO 
      // redirect to front-end, with ?client_state=xx
    }
    
    $json = (array) $json;
    $json['debug'] = [
      'code' => $code,
      'state' => $state,
      'client_state' => $client_state,
      'access_token' => $access_token,
      'refresh_token' => $refresh_token,
    ];
    
    return $this->sendResponse( $json );
  }
  
  public function token(Request $request) {
    
    $refresh_token = $request->input('refresh_token') ?? null;
    
    $client_state = $request->input('client_state') ?? null;
    if ( ! $refresh_token && ! $client_state ) {
      return $this->sendError('spotify error: refresh token and/or state not found');
    }
    
    // lookup refresh token where client_state = xx 
    $has_token = false;
    if ( $client_state != '' ) {
      $rows = DB::table('spotify_tokens')->where('client_state', $client_state)->limit(1)->get();
      if ( count( $rows ) < 1 ) {
        return $this->login( $request );
      }
      
      $expires = new Carbon( $rows[0]->expires_at, self::PRINCE_TIMEZONE );
      $expires->subSeconds( 15*60 ); // refresh any tokens that will expire in 15 mins
      $now = Carbon::now(self::PRINCE_TIMEZONE);
      
      /*
      echo $now;
      echo PHP_EOL;
      echo $expires;
      exit();
      */
      
      if ( $expires->isAfter( $now ) ) {
        return $this->sendResponse( [
          'access_token' => $rows[0]->access_token,
          'was_refreshed' => false
        ] );
      }
      
      $refresh_token = $rows[0]->refresh_token;
      $has_token = true;
    }
    
    $payload = [
      'refresh_token' => $refresh_token,
      'grant_type' => 'refresh_token'
    ];

    try {

      $response = Http::withBasicAuth(config('spotify.spotify_client_id'), config('spotify.spotify_client_secret'))
        ->asForm()
        ->post( 'https://accounts.spotify.com/api/token', $payload );
        
    } catch ( \Throwable $e ) {
      return $this->sendError( 'spotify token request error: ' . $e->getMessage() );
    }
    
    $json = $response->body();
    $json = json_decode( $json );
    
    $error = $json->error ?? null;
    if ( $error ) {
      return $this->sendError( 'spotify error: ' . ($json->error_description ?? 'unknown error') );
    }
    
    $access_token = $json->access_token ?? null;
    if ( ! $access_token ) {
      return $this->sendError('spotify error: access token not found');
    }
    
    $json = (array) $json;
    $json['was_refreshed'] = true;
    $json['debug'] = [
      'client_state' => $client_state,
      'access_token' => $access_token,
      'refresh_token' => $refresh_token,
    ];
    
    // update or insert DB where client_state = xx
    if ( $has_token && $client_state != '' ) {
      
      DB::table('spotify_tokens')
        ->where('client_state', $client_state)
        ->update([
          'access_token' => $access_token,
          'expires_at' => Carbon::now(self::PRINCE_TIMEZONE)->addSeconds( $json->expires_in ?? 3600 )->format('Y-m-d H:i:s'),          
          'updated_at' => Carbon::now(self::PRINCE_TIMEZONE)->format('Y-m-d H:i:s'),
        ]);
        
    }
    
    return $this->sendResponse( $json );
  }
  
}
