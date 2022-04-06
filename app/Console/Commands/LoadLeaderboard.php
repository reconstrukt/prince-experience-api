<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

use MrShan0\PHPFirestore\FirestoreClient;
use MrShan0\PHPFirestore\FirestoreDocument;
use MrShan0\PHPFirestore\Fields\FirestoreTimestamp;

class LoadLeaderboard extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'load:leaderboard';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hydrate the Canada leaderboard with summarized user points';
    
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
      $province_players = [];
      
      // scan user docs in descending points order,
      // til we hit a zero-point user 

      while ( $has_more ) {
        
        $collection = $firestoreClient->listDocuments('user_points', [
          'pageSize' => $per_page,
          'pageToken' => $page_token,
          'orderBy' => 'pointsDonated desc'
        ]);
        
        $docs = $collection['documents'] ?? [];
        if ( count( $docs ) < 1 ) break;
        
        // print_r( $docs );
        
        foreach ( $docs as $doc ) {
          
          $prov = null;
          try {
            $prov = $doc->get('province');
          } catch (\Throwable $e) {}
          if ( ! array_key_exists( $prov, $province_points ) ) {
            continue;
          }
          
          $points = 0;
          try {
            $points = $doc->get('pointsDonated');
          } catch (\Throwable $e) {}
          if ( $points == 0 ) {
            // stop scanning docs when we encounter first zero point user            
            $has_more = false;
            break;
          }
          
          // increment province score summary
          $province_points[ $prov ] += $points;
          
          // increment province player count
          if ( ! isset( $province_players[ $prov ] ) ) {
            $province_players[ $prov ] = 0;
          }
          $province_players[ $prov ] += 1;
          
          $sum += $points;          
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
      
      // sanity check 
      $vals = array_sum( array_values( $province_points ) );
      $this->info('user sum is ' . $sum . ', province sum is ' . $vals);
      
      // sort provinces 
      asort( $province_points );
      $province_points = array_reverse( $province_points );
      print_r( $province_points );
      
      // save to collection 
      
      foreach ( $province_points as $province => $total_points ) {
        
        $id = Str::slug($province, '-');
        
        $total_players = $province_players[ $province ] ?? 0;
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
      
      // log 
      
      $this->info('scanned ' . $tot . ' user docs');
      
      return Command::SUCCESS;
    }    
    
}
