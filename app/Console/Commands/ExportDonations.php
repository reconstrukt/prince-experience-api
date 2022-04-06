<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

use MrShan0\PHPFirestore\FirestoreClient;
use MrShan0\PHPFirestore\FirestoreDocument;
use MrShan0\PHPFirestore\Fields\FirestoreArray;
use MrShan0\PHPFirestore\Fields\FirestoreObject;
use MrShan0\PHPFirestore\Fields\FirestoreTimestamp;

class ExportDonations extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:donations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export the donation history to a report';
    
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
      
      $firestoreClient = new FirestoreClient(config('services.firebase.project_id'), config('services.firebase.api_key'), [
        'database' => config('services.firebase.database'),
      ]);
      
      $firestoreClient
        ->authenticator()
        ->signInEmailPassword(config('services.firebase.username'), config('services.firebase.password'));
      
      //
      
      $per_page = 1000;    
      $page_token = null;
      $has_more = true;
      
      $tot = 0;
      $sum = 0;
      
      $csvpath = storage_path('app/public/donation_history_' . date('YmdHis') . '.csv');      
      $handle = fopen( $csvpath, 'w' );
      fputcsv($handle, [
        'Charity',
        'As of',
        'Donated Points',
        'Dollars Donated',
      ]);
      
      while ( $has_more ) {
        
        $collection = $firestoreClient->listDocuments('donation_history', [
          'pageSize' => $per_page,
          'pageToken' => $page_token,
          'orderBy' => 'asof asc'
        ]);
        
        $docs = $collection['documents'] ?? [];
        if ( count( $docs ) < 1 ) break;
        
        // print_r( $docs );
        
        foreach ( $docs as $doc ) {
          
          $charity = null;
          try {
            $charity = $doc->get('charity');
          } catch (\Throwable $e) {}
          
          $asof = '';
          try {
            $asof = $doc->get('asof');
            $asof = $asof->parseValue();
            $asof = date('Y-m-d', strtotime( $asof ));
          } catch (\Throwable $e) {}
          
          $points = 0;
          try {
            $points = $doc->get('points');
          } catch (\Throwable $e) {}
          
          $dollars = round( ( $points / 1000 ) * 5, 2 ); // 1000 points = $5 CAD
          
          fputcsv($handle, [
            $charity,
            $asof,
            $points,
            $dollars
          ]);
          
          $tot++;
        }
        
        $page_token = $collection['nextPageToken'] ?? false;
        if ( ! $page_token ) {
          break;
        }
      }
      
      // log 
      
      $this->info('added ' . $tot . ' entries to donation_history report');
      $this->info('generated CSV file here: ' . $csvpath);
      fclose($handle);
      
      // upload to s3 
      $filename = basename($csvpath);
      $uri = Storage::disk('s3')->putFileAs('reports', $csvpath, $filename, 'public');
      $url = Storage::disk('s3')->url($uri);
      
      $this->info('uploaded to S3 here: ' . $url);
      
      unlink($csvpath);
      
      return Command::SUCCESS;
    }    
    
}
