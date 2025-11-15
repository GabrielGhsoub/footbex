<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('double_point_weekly_matches', function (Blueprint $table) {
            $table->id();
            $table->string('week_identifier')->unique()->comment('e.g., 2025-20 for gameweek 20');
            $table->unsignedBigInteger('match_id')->comment('External match ID from football-data.org');
            $table->string('home_team_name');
            $table->string('away_team_name');
            $table->foreignId('set_by')->constrained('users')->comment('Admin who set this match');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('double_point_weekly_matches');
    }
};
