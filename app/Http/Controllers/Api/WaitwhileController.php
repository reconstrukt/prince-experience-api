<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;

class WaitwhileController extends BaseController {
	
  public function __construct() {

	}
  
  public function contest(Request $request) {
    
    $validator = Validator::make($request->all(), [
      'first_name' => 'required',
      'last_name' => 'required',
      'phone' => 'required',
      'email' => 'required|email',
    ]);
    if ($validator->fails()) {
			return $this->sendError($validator->errors(), 400);
		}    
    
    $customer = [
      'name' => $request->input('first_name') . ' ' . $request->input('last_name'),
      'firstName' => $request->input('first_name'),
      'lastName' => $request->input('last_name'),
      'phone' => $request->input('phone'),
      'email' => $request->input('email'),
    ];
    
    $address = [];
    $address[] = $request->input('street1');
    if ( $request->input('street2') != null ) $address[] = $request->input('street2');
    $address[] = $request->input('city') . ', ' . $request->input('state') . ' ' . $request->input('zip');
    $notes = [
      'content' => implode(PHP_EOL, $address)
    ];
    
    // create customer 
    /*
    $headers = [
      'apikey' => config('services.waitwhile.apikey'),
      'Accept' => 'application/json',
      'Content-Type' => 'application/json'
    ];
    
    $response = Http::withHeaders( $headers )
      ->withBody( json_encode( $customer ), 'application/json' )
      ->post( 'https://api.waitwhile.com/v2/customers' );
      
    $json = $response->body();
    $customer = json_decode( $json );
    $cid = $customer->id ?? null;
    
    if ( $cid == null ) {
      return $this->sendError('WaitWhile API error: no customer ID found');
    }
    
    $url = 'https://api.waitwhile.com/v2/customers/' . $cid . '/notes';
    $response = Http::withHeaders( $headers )
      ->withBody( json_encode( $notes ), 'application/json' )
      ->post( $url );
    
    $json = $response->body();
    $note = json_decode( $json );
    */
    
    return $this->sendResponse([
      'customer' => [], //$customer,
      'note' => [], //$note,
    ]);
  }
  
}
