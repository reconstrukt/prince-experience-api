<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

use MrShan0\PHPFirestore\FirestoreClient;
use MrShan0\PHPFirestore\FirestoreDocument;
use MrShan0\PHPFirestore\Fields\FirestoreTimestamp;

class ClearLeaderboard extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:leaderboard';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear user points to reset the leaderboard';
    
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
      
      // update all user docs in descending points order

      while ( $has_more ) {
        
        $collection = $firestoreClient->listDocuments('user_points', [
          'pageSize' => $per_page,
          'pageToken' => $page_token,
          'orderBy' => 'points desc'
        ]);
        
        $docs = $collection['documents'] ?? [];
        if ( count( $docs ) < 1 ) break;
        
        foreach ( $docs as $doc ) {
          
          $id = false;
          try {
            $id = $doc->get('uid');
          } catch (\Throwable $e) {
            $this->info('err getting uid: ' . $e->getMessage());
            continue;
          }
          $doc->setInteger('pointsDonated', 0);
          $doc->setInteger('points', 0);
          $path = 'user_points/' . $id;
          
          try {
            $firestoreClient->updateDocument($path, $doc, true);
          } catch (\Throwable $e) {
            $this->info('err on save: ' . $e->getMessage());
          }
          
          $tot++;
        }
        
        if ( ! $has_more ) {
          break;
        }
        
        $page_token = $collection['nextPageToken'] ?? false;
        if ( ! $page_token ) {
          break;
        }
      }
      
      // log 
      
      $this->info('cleared ' . $tot . ' user docs');
      
      // update all provinces with a zero score
      
      $province_points = [
        'Ontario' => 0,
        'Quebec' => 0,
        'British Columbia' => 0,
        'Alberta' => 0,
        'Manitoba' => 0,
        'Saskatchewan' => 0,
        'Nova Scotia' => 0,
        'New Brunswick' => 0,
        'Newfoundland and Labrador' => 0,
        'Prince Edward Island' => 0,
      ];
      
      foreach ( $province_points as $province => $total_points ) {
        
        $id = Str::slug($province, '-');
        
        $total_players = 0;
        $total_dollars = round( ( $total_points / 1000 ) * 5, 2 ); // 1000 points = $5 CAD
        
        $document = new FirestoreDocument;
        $document->setString('province', $province);
        $document->setInteger('total_points', $total_points);
        $document->setInteger('total_players', $total_players);
        $document->setDouble('total_dollars', $total_dollars);
        $document->setTimestamp('modified', new FirestoreTimestamp);
        
        $path = 'canada_leaderboard/' . $id;
        
        $firestoreClient->updateDocument($path, $document);
      }
      
      $this->info('cleared canada leaderboard');
      
      return Command::SUCCESS;
    }    
    
}
