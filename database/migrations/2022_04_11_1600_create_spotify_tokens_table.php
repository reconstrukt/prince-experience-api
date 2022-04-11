<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpotifyTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        Schema::create('spotify_tokens', function(Blueprint $table) {
          
          $table->increments('id');
          
          $table->string('client_state')->nullable();
          $table->text('access_token')->nullable();
          $table->text('refresh_token')->nullable();
          $table->text('scopes')->nullable();          
          
          $table->integer('ttl')->nullable();
          $table->dateTime('expires_at')->nullable();
          
          $table->timestamps();
          
          //
          
          $table->index('client_state');
          
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      
    }
}
