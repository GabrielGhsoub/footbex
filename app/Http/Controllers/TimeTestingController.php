<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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

        return view('admin.time-testing', [
            'currentTimeMessage' => $currentTimeMessage,
            'prepopulatedTime' => $prepopulatedTime
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
}
