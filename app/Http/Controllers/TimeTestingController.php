<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use App\Models\Setting; // <-- ADD THIS LINE

class TimeTestingController extends Controller
{
    /**
     * Show the time testing form.
     */
    public function show()
    {
        $currentTimeMessage = '<strong>Real Time:</strong> ' . Carbon::now()->toDateTimeString();
        $prepopulatedTime = null;
        
        if (session()->has('test_time') && app()->environment('local', 'testing')) {
            $sessionTime = session('test_time');
            try {
                // Parse the stored UTC time and convert it to the app's local timezone
                $testTime = Carbon::parse($sessionTime)->setTimezone(config('app.timezone'));
                
                // For the display message
                $currentTimeMessage = '<strong>Effective Test Time:</strong> ' . $testTime->toDayDateTimeString() . 
                                        ' (' . $testTime->tzName . ')<br><small class="text-muted">Stored UTC value: ' . $sessionTime . '</small>';

                // For pre-populating the input field, format it to 'YYYY-MM-DDTHH:MM'
                $prepopulatedTime = $testTime->format('Y-m-d\TH:i');

            } catch(\Exception $e) {
                 $currentTimeMessage = "Error parsing session time: {$sessionTime}";
            }
        }

        // Fetch the current pool size from the database.
        $currentPoolSize = Setting::getValue('pool_size', '0');

        return view('admin.time-testing', [
            'currentTimeMessage' => $currentTimeMessage,
            'prepopulatedTime' => $prepopulatedTime,
            'currentPoolSize' => $currentPoolSize, // Pass the pool size to the view
        ]);
    }

    /**
     * Set the test time in the session.
     */
    public function set(Request $request)
    {
        if (!app()->environment('local', 'testing')) {
            return redirect()->route('admin.time.show')->with('error', 'Time testing is only available in local/testing environments.');
        }

        $request->validate([
            'test_time' => 'required|date',
        ]);

        try {
            // Parse the local time input and convert it to a standard UTC ISO 8601 string for storage.
            $localTime = Carbon::parse($request->input('test_time'), config('app.timezone'));
            $utcString = $localTime->setTimezone('UTC')->toIso8601String();
            
            session(['test_time' => $utcString]);
            Log::info('Test time set to: ' . $utcString);

            return redirect()->route('admin.time.show')->with('success', 'Test time has been set successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to set test time: ' . $e->getMessage());
            return redirect()->route('admin.time.show')->with('error', 'Invalid date format provided.');
        }
    }

    /**
     * Reset the test time, reverting to the real time.
     */
    public function reset()
    {
        if (session()->has('test_time')) {
            session()->forget('test_time');
            Log::info('Test time has been reset.');
            return redirect()->route('admin.time.show')->with('success', 'Test time has been reset to real time.');
        }
        return redirect()->route('admin.time.show')->with('info', 'No test time was set.');
    }

    /**
     * NEW METHOD: Update the prize pool size.
     */
    public function updatePoolSize(Request $request)
    {
        if (!app()->environment('local', 'testing')) {
            return redirect()->route('admin.time.show')->with('error', 'This feature is only available in local/testing environments.');
        }

        $request->validate([
            'pool_size' => 'required|numeric|min:0',
        ]);

        try {
            Setting::updateOrCreate(
                ['key' => 'pool_size'],
                ['value' => $request->input('pool_size')]
            );

            Log::info('Prize pool size updated to: ' . $request->input('pool_size'));

            return redirect()->route('admin.time.show')->with('success', 'Prize pool size has been updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update prize pool size: ' . $e->getMessage());
            return redirect()->route('admin.time.show')->with('error', 'Failed to update the prize pool size.');
        }
    }

    /**
     * Manually trigger the weekly bet settlement command.
     */
    public function settleBets(Request $request)
    {
        $request->validate([
            'week' => 'nullable|regex:/^\d{4}-\d{2}$/', // Validates 'YYYY-WW' format
        ]);

        try {
            $week = $request->input('week');
            $parameters = [];

            if ($week) {
                $parameters['--week'] = $week;
            }

            // Call the artisan command
            Artisan::call('bets:settle-weekly', $parameters);

            // Get the console output to display on the frontend
            $output = Artisan::output();

            Log::info("Manual bet settlement triggered via admin panel. Output:\n" . $output);

            return redirect()->route('admin.time.show')
                ->with('success', 'Bet settlement process triggered!')
                ->with('settle_output', $output);

        } catch (\Exception $e) {
            Log::error('Manual bet settlement from admin panel failed: ' . $e->getMessage());
            return redirect()->route('admin.time.show')->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }
}