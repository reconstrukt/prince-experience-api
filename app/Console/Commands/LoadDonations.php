<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

use MrShan0\PHPFirestore\FirestoreClient;
use MrShan0\PHPFirestore\FirestoreDocument;
use MrShan0\PHPFirestore\Fields\FirestoreArray;
use MrShan0\PHPFirestore\Fields\FirestoreObject;
use MrShan0\PHPFirestore\Fields\FirestoreTimestamp;

class LoadDonations extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'load:donations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hydrate the donations collection and generate a report';
    
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
      
      $charities = [
        'Community Giving' => 0,
        'Innovating Healthcare' => 0,
        'Connecting Canada' => 0,
        'Caring for the Environment' => 0,
      ];
      
      // scan user docs in descending points order,
      // til we hit a zero-point user 

      while ( $has_more ) {
        
        $collection = $firestoreClient->listDocuments('donations', [
          'pageSize' => $per_page,
          'pageToken' => $page_token,
          'orderBy' => 'pointsDonated desc'
        ]);
        
        $docs = $collection['documents'] ?? [];
        if ( count( $docs ) < 1 ) break;
        
        // print_r( $docs );
        
        foreach ( $docs as $doc ) {
          
          $charity = null;
          try {
            $charity = $doc->get('charity');
          } catch (\Throwable $e) {}
          if ( ! array_key_exists( $charity, $charities ) ) {
            continue;
          }
          
          $points = 0;
          try {
            $points = $doc->get('pointsDonated');
          } catch (\Throwable $e) {}
          
          // stash current donated points
          $charities[ $charity ] = $points;
          
          $tot++;
        }
        
        $page_token = $collection['nextPageToken'] ?? false;
        if ( ! $page_token ) {
          break;
        }
      }
      
      $this->info('found ' . $tot . ' valid/matching charities');
      
      // save to collection 
      
      $updates = 0;
      
      foreach ( $charities as $charity => $current_points ) {
        
        $document = new FirestoreDocument;
        
        $id = Str::slug($charity, '-');
        $document->setString('charityId', $id);
        
        $document->setString('charity', $charity);
        $document->setInteger('points', $current_points);
        $document->setTimestamp('asof', new FirestoreTimestamp);
        
        $firestoreClient->addDocument('donation_history', $document);
        
        $updates++;
      }
      
      // log 
      
      $this->info('added ' . $updates . ' entries to donation_history');
      
      return Command::SUCCESS;
    }    
    
}
