<?php

namespace App\Library;

use Carbon\Carbon;

class GoogleCSVClient {
  
  public $filename;
  public $feed_url;
  public $folder;
  
  public function __construct( $feed_url ) {
    
    $this->folder = config('filesystems.disks.temp.root') . '/' . uniqid();
    mkdir( $this->folder );
    
    $this->feed_url = $feed_url;
    
  }

  public function download() {
    
    if ( ! $this->feed_url ) throw new \Exception('CSV feed url not set');

    $this->filename = $this->folder . '/google-csv-' . date('Ymd-His') . '.csv';

    set_time_limit(0);

    $handle = fopen($this->filename, 'w');
    $ch = curl_init($this->feed_url);

    curl_setopt($ch, CURLOPT_TIMEOUT, 50);
    curl_setopt($ch, CURLOPT_FILE, $handle);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    curl_exec($ch);
    curl_close($ch);
    fclose($handle);

    if (filesize($this->filename) == 0) {
      if (file_exists($this->filename)) {
        unlink($this->filename);
        $this->filename = null;
      }
      throw new \Exception('Downloaded CSV file is empty');
    }

    return true;
  }
  
  public function read() {
    
    $handle = fopen($this->filename, 'r+');
    if ( !$handle ) {
      throw new \Exception('Read error on downloaded CSV here: ' . $this->filename);
    }
    
    $tot = 0;
    $cols = [];
    $rows = [];
    
    while (($data = fgetcsv($handle, 0, ",")) !== false) {

      if ($tot == 0) {
        // parse header row
        for ($c = 0; $c < count($data); $c++) {
          $cols[] = strtolower(trim($data[$c]));
        }
        $tot++;
        continue;
      }
      
      // parse rows
      $row = [];
      for ($c = 0; $c < count($data); $c++) {
        $row[$cols[$c]] = trim( $data[$c] );
      }
      $rows[] = $row;
      
      $tot++;
    }

    return $rows;
  }
  
  public function cleanup() {    
    if ( $this->filename ) {
      // delete the CSV file
      unlink( $this->filename );
      $this->filename = null;
    }
    if ( $this->folder ) {
      // delete our scratch directory
      rmdir( $this->folder );
      $this->folder = null;
    }
    return true;
  }
}
