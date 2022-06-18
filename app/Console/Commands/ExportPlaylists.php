<?php

namespace App\Console\Commands;

use App\Library\GoogleCSVClient;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ExportPlaylists extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:playlists';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export Playlists';
    
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
      
      $client = new GoogleCSVClient( 'https://docs.google.com/spreadsheets/d/e/2PACX-1vQvttlAp-6YoFYpRppEcAecqPn0F9_oHXyIjiRzqzYLzCqp2jMYShi-Tv4j4ZckLInzmL-gMvmX_NLr/pub?gid=675402443&single=true&output=csv' );
      
      $rows = [];
      try {        
        $client->download();
        $rows = $client->read();        
      } catch (\Throwable $e) {
        $this->info( 'ERROR: ' . $e->getMessage() );
        return Command::FAILURE;
      }
      $client->cleanup();
      
      $tot = 0;
      $pls = 0;
      $json = [];
      
      foreach ( $rows as $row ) {
        
        $persona = $row['persona uri'] ?? null;
        $track = $row['track'] ?? null;
        $album = $row['album'] ?? null;
        
        if ( $track == '' ) {
          // empty line in sheet 
          continue;
        }
        
        // $filename = $row['filename'] ?? null;
        
        $filename = $row['link'] ?? null;
        $filename = parse_url( $filename, PHP_URL_PATH ); 
        $filename = pathinfo( $filename, PATHINFO_BASENAME );
        $filename = urldecode( $filename );
        $filename = str_replace( ' ', '_', $filename );
        $filename = str_replace( ['.wav','.aiff'], '.mp3', $filename );
        
        if ( $filename == '' ) {
          $this->info( 'missing track for: ' . $track );
          continue;
        }
        
        $path = storage_path( 'tracks/' . $filename );
        if ( ! file_exists( $path ) ) {
          $this->info( 'file not found: ' . $filename );
          continue;
        }
        
        if ( $persona && $track && $album ) {
          if ( ! isset( $json[ $persona ] ) ) {
            $json[ $persona ] = [];
            $pls++;
          }          
          $json[ $persona ][] = [
            'persona' => $persona,
            'track' => $track,
            'album' => $album,
            'filename' => $filename,
          ];
          $tot++;
        }
      }
      
      $contents = json_encode( $json, JSON_PRETTY_PRINT );
      $path = Storage::put('playlists.json', $contents);
      
      //print_r( $json );
      
      $this->info( 'parsed ' . $tot . ' tracks into ' . $pls . ' playlists' );
      $this->info( 'file exported to: ' . storage_path( 'playlists.json' ) );
      
      return Command::SUCCESS;
    }
    
}
