<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BaseController extends Controller
{
    
    /**
     * success response method.
     * @param $result
     * @param $message
     * @return Response
     */
    public function sendResponse($result, $message = 'OK', $meta = null) {
      $response = [
        'success' => true,
        'message' => $message,
        'data' => $result,
      ];
      if ( is_array( $meta ) ) {
        $response['meta'] = $meta;
      }
      return response()->json($response, 200);
    }
    
    /**
     * return error response.
     * @param $error
     * @param array $errorMessages
     * @param int $code
     * @return Response
     */
    public function sendError($error, $code = 400, $field_errors = null)
    {
      
      // TODO
      //  .message = always string message
      //  .errors  = always object, with field-level error messages (new)
      //  .data    = should not be error code, should be empty  
      
      $message = is_string( $error ) ? 
        $error : "Oops! That's an error";
      
      $errors = $field_errors ?? (object)[]; // assure this is an empty object; not empty array
      if ( ! is_string( $error ) ) {
        $errors = $error;
      }
      
      $response = [
        'success' => false,
        'message' => $message,
        'errors' => $errors
      ];
      
      return response()->json($response, $code);
    }
    
    /**
     * return unauthorized response.     
     */
    public function unauthorized()
    {
      return $this->sendError("Sorry, you're not authorized to do that", 401);
    }
    
    /**
     * return general success response. 
     */
    public function ok($message = 'OK') {
      return $this->sendResponse([], $message);
    }
    
    /**
     * return data collection response. 
     */
    protected function collection($rows = [], $meta = []) {
      
      $tot = count( $rows );      
      if ( ! isset( $meta['count'] ) ) {
        $meta['count'] = $tot;
      }
      
      if ( isset( $meta['total_pages'] ) && isset( $meta['page'] ) ) {
        if ( $meta['page'] < $meta['total_pages'] ) {
          $meta['next_url'] = append_url_param( url()->full(),[
            'page' => $meta['page'] + 1
          ] );
        }
        if ( $meta['page'] > 1 ) {
          $meta['prev_url'] = append_url_param( url()->full(), [
            'page' => $meta['page'] - 1
          ] );
        }
      }
      
      // sniff check, try transforming for API      
      /*
      if ( count( $rows ) > 0 ) {
        if ( is_object( $rows[0] ) ) {
          if ( method_exists( $rows[0], 'forAPI' ) ) {
            $_rows = $rows;
            $rows = [];
            foreach ( $_rows as $row ) {
              $rows[] = $row->forAPI();
            }
          }
        }
      }
      */
      
      $response = [
        'success' => true,
        'message' => $tot . ' item' . ($tot==1?'':'s') . ' found',
        'meta' => $meta,
        'data' => $rows,
      ];
      return response()->json($response, 200);
    }
    
    
}
