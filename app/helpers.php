<?php

if (!function_exists('asset_version')) {

  function asset_version() {
    $filename = dirname(dirname(__DIR__));
    $filename .= DIRECTORY_SEPARATOR . 'ASSET_VERSION';
    if (file_exists($filename)) {
      return trim(file_get_contents($filename, 'r'));
    } else {
      return time();
    }
  }

}

if (!function_exists('uuid4')) {

  function uuid4() {
    return (string) \Illuminate\Support\Str::uuid();
  }

}

if (!function_exists('s3_config')) {

  function s3_config() {
    $region = config('aws.s3_region');
    $bucket = config('aws.s3_bucket');
    $acl = config('aws.s3_acl');

    $middle = '';
    if ($region != 'us-east-1') {
      $middle = '-' . $region;
    }

    $uploader = new \EddTurtle\DirectUpload\Signature(
      config('aws.access_key'),
      config('aws.secret_key'),
      config('aws.s3_bucket'),
      config('aws.s3_region'),
      [
        'acl' => config('aws.s3_acl'),
        'max_file_size' => intval(config('aws.s3_max_file_size')),
      ]
    );

    $inputs = $uploader->getFormInputs();

    return [
      's3' => (object)[
        'bucket' => $bucket,
        'acl' => $acl,
        'access_key' => config('aws.access_key'),
        'form_action' => 'https://s3' . $middle . '.amazonaws.com/' . urlencode($bucket),
        'inputs' => $inputs,
      ],
    ];
  }

}

if (!function_exists('s3_md5sum')) {

  function s3_md5sum($uri) {
    try {
      $client = new \Aws\S3\S3Client([
        'version' => 'latest',
        'region' => config('aws.s3_region'),
        'credentials' => [
          'key' => config('aws.access_key'),
          'secret' => config('aws.secret_key'),
        ],
      ]);

      $result = $client->headObject([
        'Bucket' => config('aws.s3_bucket'),
        'Key' => $uri,
      ]);

      return trim(trim($result->get('ETag')), '"');
    } catch (\Exception $ex) {
      return '';
    }
  }

}
  
if (!function_exists('s3_authenticated_url')) {

  function s3_authenticated_url($uri, $expires = '+30 minutes') {

    $client = new \Aws\S3\S3Client([
      'version' => 'latest',
      'region' => config('aws.s3_region'),
      'credentials' => [
        'key' => config('aws.access_key'),
        'secret' => config('aws.secret_key'),
      ],
    ]);

    $cmd = $client->getCommand('GetObject', [
      'Bucket' => config('aws.s3_bucket'),
      'Key' => $uri,
    ]);

    $request = $client->createPresignedRequest($cmd, '+30 minutes');

    $url = (string) $request->getUri();

    return $url;
  }

}

if (!function_exists('s3_rename')) {

  function s3_rename($source, $dest) {
    
    $region = config('aws.s3_region');
    $bucket = config('aws.s3_bucket');
    $acl = config('aws.s3_acl');
    
    $client = new \Aws\S3\S3Client([
      'version' => 'latest',
      'region' => $region,
      'credentials' => [
        'key' => config('aws.access_key'),
        'secret' => config('aws.secret_key'),
      ],
    ]);
    
    try {

      $client->copyObject([
        'ACL' => $acl,
        'Bucket' => $bucket,
        'CopySource' => $bucket . '/' . $source,
        'Key' => $dest,
        'MetadataDirective' => 'REPLACE'
      ]);

      return true;

    } catch (Exception $e) {
      
      return false;
      
    }
    
    return false;
    
  }

}

if (!function_exists('twilio_format')) {

  function twilio_format($number) {
    
    // "+19173550786";
    // validate ph number
    $number = preg_replace( "/[^0-9]/", "", $number );
    $uslen = 10;
    
    if ( $number == '' ) {
      return false;
    }
    if ( ! ctype_digit( $number ) ) {
      return false;
    }
    
    if ( strlen( $number ) == $uslen ) {
      $number = '1' . $number;
    }
    if ( substr( $number, 0, 1 ) != '+' ) {
      $number = '+' . $number;
    }
    
    return $number;
    
  }

}

if (! function_exists('ga_client_id')) {
  function ga_client_id() {
    $sga = new \App\SimpleGA( env('GA_TRACKING_ID') );
    return $sga->data['cid'] ?: '';
  }
}

if (! function_exists('ga_event')) {
  function ga_event( $c, $a, $l = '' ) {    
    $sga = new \App\SimpleGA( env('GA_TRACKING_ID') );
    $ok = $sga->pageview();
    $ok = $sga->event( $c, $a, $l );
  }
}

if (! function_exists('slack_message')) {
	function slack_message( $message, $opt = array() ) {
    
    $payload = array_merge( [
      'text' => $message
    ], $opt );
    
    $response = \Illuminate\Support\Facades\Http::asForm()
      ->post('https://hooks.slack.com/services/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', [
        'payload' => json_encode( $payload )
      ]);
	}
}

if (! function_exists('truncate_filename')) {
	function truncate_filename( $filename, $maxlen = 20 ) {
    
    $filename = basename( $filename );
    if ( strlen( $filename ) <= $maxlen ) {
      return $filename;
    }
    
    $name = pathinfo( $filename, PATHINFO_FILENAME );
    $ext = pathinfo( $filename, PATHINFO_EXTENSION );
    
    $trimto = $maxlen - strlen( $ext );
    $trimleft = ceil( $trimto / 2 );
    $trimright = floor( $trimto / 2 );
    
    $left = substr( $name, 0, $trimleft );
    $right = substr( $name, -1 * $trimright );
    
    return $left . '...' . $right . '.' . $ext;
	}
}

if (! function_exists('relevantize')) {
	function relevantize( $datetime, $format = 'M jS' ) {
    
    if ( is_string( $datetime ) ) {
      $datetime = new \Carbon\Carbon( $datetime, 'America/New_York' );
    }
    
    if ( $datetime->isYesterday() ) {
      return 'Yesterday at ' . $datetime->format('g:ia');
    }
    if ( $datetime->isToday() ) {
      return 'Today at ' . $datetime->format('g:ia');
    }
    
    // i.e. "Wednesday at 5:00pm"
    $old = new \Carbon\Carbon( strtotime('-5 days'), 'America/New_York' );
    if ( $datetime->greaterThan( $old ) ) {
      return $datetime->format( 'l' ) . ' at ' . $datetime->format('g:ia');
    }
    
    // i.e. "3 weeks ago"
    $older = new \Carbon\Carbon( strtotime('-1 month'), 'America/New_York' );
    if ( $datetime->greaterThan( $older ) ) {
      return $datetime->diffForHumans();
    }
   
    // i.e. "Dec 10th at 5:15pm"
    $now = new Carbon\Carbon( 'America/New_York' );
    if ( ! $datetime->isSameYear( $now ) ) {
      $format = $format . ' Y';
    }
    return $datetime->format( $format );
	}
}

if (! function_exists('commafy')) {
	function commafy( $list ) {
    $last = array_pop($list);
    if ( count( $list ) < 1 ) return $last;
    return implode(', ', $list) . ' and ' . $last;
  }
}

if (! function_exists('pluralize')) {
	function pluralize( $word, $count ) {
    
    if ( $word === 'look' || $word === 'Look' ) {
      if ( $count === 1 ) {
        return $word === 'look' ? 'looks' : 'Looks';        
      } else {
        return $word === 'look' ? 'look' : 'Look';
      }      
    }
    
    if ( $count === 1 ) return $word;
    
    $word = trim( $word );
    if ( $word === 'is' || $word === 'Is' ) {
      return $word === 'is' ? 'are' : 'Are';
    }
    if ( $word === 'this' || $word === 'This' ) {
      return $word === 'this' ? 'these' : 'These';
    }
    if ( $word === 'it' || $word === 'It' ) {
      return $word === 'it' ? 'they' : 'They';
    }
    
    return \Illuminate\Support\Str::plural($word, $count);
  }
}

if (! function_exists('getSql')) {
  function getSql($query) {    
    
    return array_reduce($query->getBindings(), function($sql, $binding) {
      return preg_replace('/\?/', is_numeric($binding)
        ? $binding
        : "'".$binding."'", $sql, 1);
    }, $query->toSql());
    
    /*
    $sql = str_replace(array('?'), array('\'%s\''), $query->toSql());
    $sql = vsprintf($sql, $query->getBindings());
    return $sql;
    */
  }
}
