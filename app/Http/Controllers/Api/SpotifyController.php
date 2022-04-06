<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SpotifyController extends BaseController {
	
  public function __construct() {

	}
  
  public function login(Request $request) {
    
    return $this->ok('login not implemented');
    
  }
  
  public function token(Request $request) {
    
    return $this->ok('token not implemented');
    
  }
  
}
