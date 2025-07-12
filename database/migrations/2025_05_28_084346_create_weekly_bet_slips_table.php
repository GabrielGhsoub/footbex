<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('weekly_bet_slips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('week_identifier')->comment('e.g., YYYY-WW format, like 2025-23'); // ISO 8601 week date
            $table->timestamp('betting_closes_at')->comment('Timestamp of the first match of the week');
            $table->boolean('is_submitted')->default(false);
            $table->integer('total_score')->nullable();
            $table->enum('status', ['open', 'submitted', 'processing', 'settled', 'closed_early'])->default('open');
            // 'closed_early' if betting window closed before user could submit (e.g. first match started)
            $table->timestamps();

            $table->unique(['user_id', 'week_identifier'], 'user_week_unique_slip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_bet_slips');
    }
};