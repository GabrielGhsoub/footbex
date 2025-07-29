<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB; // <-- Make sure this is included

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Use a raw SQL statement to modify the column directly
        DB::statement('ALTER TABLE weekly_bet_slips MODIFY betting_closes_at TIMESTAMP NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert the change using a raw SQL statement
        DB::statement('ALTER TABLE weekly_bet_slips MODIFY betting_closes_at TIMESTAMP NOT NULL');
    }
};