<?php

namespace App\Http\Controllers\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

trait ProvidesEffectiveTime
{
    /**
     * Defines the "current" time for the application.
     * It uses a test time from the session if available (in local/testing env),
     * otherwise, it defaults to the real current time.
     */
    private function getEffectiveTime(): Carbon
    {
        // Check for a test time in the session, only in local/testing environment.
        if (session()->has('test_time') && app()->environment('local', 'testing')) {
            try {
                $testDateTimeString = session('test_time');
                // Parse the UTC string and set the timezone to the application's configured timezone.
                return Carbon::parse($testDateTimeString)->setTimezone(config('app.timezone'));
            } catch (\Exception $e) {
                Log::warning('Invalid test_time in session: ' . session('test_time') . '. Defaulting to Carbon::now(). Error: ' . $e->getMessage());
            }
        }
        // Default to the real current time.
        return Carbon::now();
    }
}
