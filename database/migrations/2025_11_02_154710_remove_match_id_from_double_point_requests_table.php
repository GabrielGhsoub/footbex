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
        Schema::table('double_point_requests', function (Blueprint $table) {
            $table->dropColumn('match_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('double_point_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('match_id')->after('week_identifier')->comment('External match ID from football-data.org');
        });
    }
};
