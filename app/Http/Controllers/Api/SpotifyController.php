<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SpotifyController extends BaseController {
	
  public const SEPERATOR = '|';
  
  public function __construct() {

	}
  
  public function login(Request $request) {
    
    // return $this->ok('login not implemented');
    
    $client_state = $request->input('state');
    
    $scope = "streaming,user-read-email,user-read-private";
    $state = uniqid() . self::SEPERATOR . $client_state;
    $params = [
      'response_type' => 'code',
      'client_id' => config('spotify.spotify_client_id'),
      'scope' => $scope,
      'redirect_uri' => url('/api/v1/spotify/callback'),
      'state' => $state
    ];
    
    $url = 'https://accounts.spotify.com/authorize/?' . http_build_query( $params );
    
    return $this->sendResponse([
      'url' => $url
    ]);
  }
  
  public function callback(Request $request) {
    
    // return $this->ok('callback not implemented');
    
    $code = $request->input('code') ?? null;
    $state = $request->input('state') ?? null;
    if ( ! $code || ! $state ) {
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
    $client_state = $client_state[1];
    
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
    $state = $request->input('state') ?? null;
    if ( ! $refresh_token || ! $state ) {
      return $this->sendError('spotify error: refresh token and/or state not found');
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
    
    // cache the access -and- refresh tokens for the given client state 
    
    $access_token = $json->access_token ?? null;
    if ( ! $access_token ) {
      return $this->sendError('spotify error: access token not found');
    }
    
    $json = (array) $json;
    $json['debug'] = [
      'client_state' => $state,
      'access_token' => $access_token,
      'refresh_token' => $refresh_token,
    ];
    
    return $this->sendResponse( $json );
  }
  
}
