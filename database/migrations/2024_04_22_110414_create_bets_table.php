<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('match_id');
            $table->string('home_team'); // Adding home team name
            $table->string('away_team'); // Adding away team name
            $table->decimal('amount', 8, 2);
            $table->integer('home_score'); // Score predicted for the home team
            $table->integer('away_score'); // Score predicted for the away team
            $table->dateTime('match_date');  
            $table->boolean('is_winner')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'match_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('bets');
    }
};
