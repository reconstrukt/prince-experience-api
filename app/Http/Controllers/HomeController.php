<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller {
	
  public function __construct() {

	}
  
  public function index(Request $request) {
    
    $params = [
      'show_dialog' => 'true',
    ];
    
    $state = $request->input('client_state') ?? false;
    if ( $state ) {
      $params['client_state'] = $request->input('client_state');
    }
    
    $url = url('/api/v1/spotify/login') . '?' . http_build_query( $params );
    
    try {
      $response = Http::get($url);
      $json = $response->json();
    } catch ( \Throwable $e ) {
      exit('Spotify API request failed.');
    }
    
    $spotify_login_url = $json['data']['url'] ?? null;
    if ( ! $spotify_login_url ) {
      exit('Spotify API request did not return valid login URL.');
    }
    
    return redirect( $spotify_login_url );
  }
  
}
