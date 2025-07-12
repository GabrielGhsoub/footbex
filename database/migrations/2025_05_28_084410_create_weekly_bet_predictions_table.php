<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('weekly_bet_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weekly_bet_slip_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('match_id')->comment('External match ID from football-data.org');
            $table->string('home_team_name');
            $table->string('away_team_name');
            $table->enum('predicted_outcome', ['home_win', 'draw', 'away_win']);
            $table->enum('actual_outcome', ['home_win', 'draw', 'away_win'])->nullable();
            $table->integer('points_awarded')->default(0);
            $table->timestamp('match_utc_date_time');
            $table->timestamps();

            $table->index('match_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_bet_predictions');
    }
};