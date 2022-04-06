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
    
    $state = $request->input('state') ?? date('YmdHis') . '-' . uniqid();
    $url = url('/api/v1/spotify/login') . '?show_dialog=true&state=' . $state;
    
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
