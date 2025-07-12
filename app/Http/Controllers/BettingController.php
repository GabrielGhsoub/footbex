<?php

namespace App\Http\Controllers;

use App\Models\WeeklyBetSlip;
use App\Models\WeeklyBetPrediction;
use App\Services\FootballDataService;
use Illuminate\Http\Request; // Keep Request for potential future use, though not for test_date now
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BettingController extends Controller
{
    protected FootballDataService $footballDataService;

    public function __construct(FootballDataService $footballDataService)
    {
        $this->middleware('auth');
        $this->footballDataService = $footballDataService;
    }

    /**
     * Defines the "current" time for testing purposes.
     * Change the $testDateTimeString to simulate different moments.
     * Set to null or empty to use the real current time.
     */
    private function getEffectiveTime(): Carbon
    {
        // --- !!! EDIT THIS LINE FOR TESTING !!! ---
        // Examples:
        // $testDateTimeString = '2025-05-19T17:00:00Z'; // To test *before* a typical Monday 18:30 match
        // $testDateTimeString = '2025-05-19T20:00:00Z'; // To test *after* a typical Monday 18:30 match
        // $testDateTimeString = '2025-05-25T23:59:59Z'; // To test end of week
        // $testDateTimeString = null; // Set to a date string like 'YYYY-MM-DDTHH:MM:SSZ' or null
        $testDateTimeString = '2025-03-09T23:59:59Z';

        if (!empty($testDateTimeString) && app()->environment('local', 'testing')) { // Only allow in local/testing
            try {
                // Ensure it's parsed and then set to the application's configured timezone
                // If your string includes 'Z', Carbon parses it as UTC.
                // Then convert to app's timezone for consistent internal logic,
                // but comparisons with API's UTC dates will need to be UTC-aware.
                return Carbon::parse($testDateTimeString)->setTimezone(config('app.timezone'));
            } catch (\Exception $e) {
                Log::warning('Invalid testDateTimeString for getEffectiveTime: ' . $testDateTimeString . '. Defaulting to Carbon::now(). Error: ' . $e->getMessage());
            }
        }
        return Carbon::now(); // Defaults to real current time
    }


    /**
     * Helper function to determine the first match UTC time from a list of matches.
     */
    private function getFirstMatchUtcFromMatches(array $matches): ?Carbon
    {
        $firstMatchUtc = null;
        if (empty($matches)) {
            return null;
        }

        foreach ($matches as $match) {
            if (isset($match['utcDate'])) {
                try {
                    // API provides dates like "2025-05-01T18:30:00Z", Carbon::parse handles 'Z' as UTC
                    $matchTime = Carbon::parse($match['utcDate']); // This will be a UTC Carbon instance
                    if ($firstMatchUtc === null || $matchTime->lt($firstMatchUtc)) {
                        $firstMatchUtc = $matchTime;
                    }
                } catch (\Exception $e) {
                    Log::error("Error parsing utcDate for match: " . ($match['id'] ?? 'N/A'), ['date' => $match['utcDate'], 'error' => $e->getMessage()]);
                    continue;
                }
            }
        }
        return $firstMatchUtc; // Returns a Carbon instance in UTC, or null
    }

    /**
     * Show the form for placing bets on the current week's matches.
     */
    public function showCurrentWeekBetForm()
    {
        $user = Auth::user();
        $effectiveNow = $this->getEffectiveTime();

        // The "betting week" is defined as 1 week prior to the effective "now"
        $bettingWeekReferenceDate = $effectiveNow->copy()->subWeeks(1);
        $bettingWeekIdentifier = $bettingWeekReferenceDate->format('o-W');

        Log::info("BettingController: showCurrentWeekBetForm", [
            'effectiveNow' => $effectiveNow->toDateTimeString(),
            'bettingWeekForSlipIdentifier' => $bettingWeekIdentifier,
        ]);

        // FootballDataService ALWAYS fetches based on Carbon::now()->subWeeks(1) (actual server time)
        $matchesResponse = $this->footballDataService->getWeeklyMatches();

        if (!$matchesResponse['success'] || empty($matchesResponse['data'])) {
            return view('betting.weekly_bet_form', [
                'matches' => [],
                'bettingOpen' => false,
                'message' => $matchesResponse['message'] ?? 'No matches available for the current betting period, or an error occurred fetching data.',
                'existingSlip' => null,
                'firstMatchTime' => null,
                'currentTime' => $effectiveNow, // Use effectiveNow for display consistency
                'weekIdentifier' => $bettingWeekIdentifier, // This is for the slip being created/viewed
            ]);
        }

        $matches = $matchesResponse['data'];
        $firstMatchUtc = $this->getFirstMatchUtcFromMatches($matches); // This is UTC

        $bettingOpen = true;
        $message = null;

        if ($firstMatchUtc === null) {
            $bettingOpen = false;
            $message = 'Match schedule details are incomplete. Betting is currently unavailable.';
            Log::warning("BettingController: No firstMatchUtc could be determined for matches fetched (week ID based on service: " . Carbon::now()->subWeeks(1)->format('o-W') . ").");
        } elseif ($effectiveNow->copy()->setTimezone('UTC')->gte($firstMatchUtc)) {
            // Compare effectiveNow (converted to UTC) with firstMatchUtc (already UTC)
            $bettingOpen = false;
            $message = 'The betting window for this week has closed. (Effective time: ' . $effectiveNow->toDateTimeString() . ' vs First Match UTC: ' . $firstMatchUtc->toDateTimeString() . ')';
            Log::info("Betting window closed.", ['effectiveNow_utc' => $effectiveNow->copy()->setTimezone('UTC')->toIso8601String(), 'firstMatchUtc' => $firstMatchUtc->toIso8601String()]);
        }

        $existingSlip = WeeklyBetSlip::where('user_id', $user->id)
            ->where('week_identifier', $bettingWeekIdentifier) // Check for slip related to the "effective" week
            ->with('predictions')
            ->first();

        if ($existingSlip && $existingSlip->is_submitted) {
            $bettingOpen = false;
            if (is_null($message)) {
                $message = 'You have already submitted your predictions for this (effective) week: ' . $bettingWeekIdentifier;
            }
        }
        
        if (!empty($matches)) {
            usort($matches, function ($a, $b) {
                if (!isset($a['utcDate']) || !isset($b['utcDate'])) return 0;
                return strcmp($a['utcDate'], $b['utcDate']);
            });
        }

        return view('betting.weekly_bet_form', [
            'matches' => $matches, // Matches are from FootballDataService (actual_now - 1 week)
            'bettingOpen' => $bettingOpen,
            'message' => $message,
            'existingSlip' => $existingSlip,
            'weekIdentifier' => $bettingWeekIdentifier, // Identifier for the slip being managed
            'firstMatchTime' => $firstMatchUtc, 
            'currentTime' => $effectiveNow
        ]);
    }

    /**
     * Store the weekly bet predictions.
     */
    public function storeCurrentWeekBet(Request $request) // Added Request back
    {
        $user = Auth::user();
        $effectiveNow = $this->getEffectiveTime();

        $bettingWeekReferenceDate = $effectiveNow->copy()->subWeeks(1);
        $bettingWeekIdentifier = $bettingWeekReferenceDate->format('o-W');

        Log::info("User {$user->id} attempting to store weekly bet for effective week {$bettingWeekIdentifier}", [
            'effectiveNow' => $effectiveNow->toDateTimeString(),
            'request_data' => $request->all()
        ]);

        // Fetch matches again - service uses actual Carbon::now()->subWeeks(1)
        $matchesResponse = $this->footballDataService->getWeeklyMatches();
        
        if (!$matchesResponse['success'] || empty($matchesResponse['data'])) {
            Log::warning("User {$user->id} submission failed: No matches found by service for store attempt.");
            return redirect()->route('betting.show_form')->with('error', 'Could not retrieve match data. Please try again.');
        }

        $firstMatchUtc = $this->getFirstMatchUtcFromMatches($matchesResponse['data']); // This is UTC

        if ($firstMatchUtc === null) {
            Log::warning("User {$user->id} submission failed: Could not determine first match time during store.");
            return redirect()->route('betting.show_form')->with('error', 'Match schedule details are incomplete. Cannot save predictions.');
        }

        // Compare effectiveNow (converted to UTC) with firstMatchUtc (already UTC)
        if ($effectiveNow->copy()->setTimezone('UTC')->gte($firstMatchUtc)) {
            Log::warning("User {$user->id} submission failed: Betting window closed for matches. Effective: {$effectiveNow->setTimezone('UTC')->toDateTimeString()} UTC, Deadline: {$firstMatchUtc->toDateTimeString()} UTC");
            return redirect()->route('betting.show_form')->with('error', 'The betting window has closed. Predictions not saved.');
        }

        $existingSlip = WeeklyBetSlip::where('user_id', $user->id)
            ->where('week_identifier', $bettingWeekIdentifier) // Check for slip related to the "effective" week
            ->first();

        if ($existingSlip && $existingSlip->is_submitted) {
            Log::warning("User {$user->id} submission failed: Already submitted for effective week {$bettingWeekIdentifier}");
            return redirect()->route('betting.show_form')->with('error', 'You have already submitted your predictions for this week period.');
        }

        $request->validate([
            'predictions' => 'required|array',
            'predictions.*.match_id' => 'required|integer',
            'predictions.*.outcome' => 'required|in:home_win,draw,away_win',
        ]);
        
        $submittedPredictions = $request->input('predictions');
        $apiMatchIds = array_column($matchesResponse['data'], 'id');
        
        if (count($submittedPredictions) !== count($apiMatchIds)) {
            Log::warning("User {$user->id} submission failed: Prediction count mismatch for effective week {$bettingWeekIdentifier}. Expected: " . count($apiMatchIds) . ", Got: " . count($submittedPredictions));
            return redirect()->back()->withInput()->with('error', 'Please make a prediction for every match shown.');
        }

        $slip = $existingSlip ?? new WeeklyBetSlip();
        $slip->user_id = $user->id;
        $slip->week_identifier = $bettingWeekIdentifier; // Use identifier from effective time
        $slip->betting_closes_at = $firstMatchUtc; // Store actual deadline of matches in UTC
        $slip->is_submitted = true;
        $slip->status = 'submitted';
        $slip->save();

        Log::info("User {$user->id} saved WeeklyBetSlip ID {$slip->id} for effective week {$bettingWeekIdentifier}");

        if ($existingSlip && !$existingSlip->is_submitted) {
            $slip->predictions()->delete();
        }
        
        $predictionsToSave = [];
        foreach ($submittedPredictions as $predictionData) {
            $matchDetail = null;
            foreach ($matchesResponse['data'] as $m) {
                if ($m['id'] == $predictionData['match_id']) {
                    $matchDetail = $m;
                    break;
                }
            }

            if (!$matchDetail || !isset($matchDetail['utcDate'], $matchDetail['home']['name'], $matchDetail['away']['name'])) {
                Log::error("Match ID {$predictionData['match_id']} (or details) submitted by user {$user->id} not found or incomplete. Slip ID {$slip->id}. Reverting.", ['detail' => $matchDetail]);
                $slip->is_submitted = false; $slip->status = 'open'; $slip->save();
                WeeklyBetPrediction::where('weekly_bet_slip_id', $slip->id)->delete(); // Clean up
                return redirect()->back()->withInput()->with('error', 'A problem occurred with match data. Please refresh and try again.');
            }
            
            if(!in_array($predictionData['match_id'], $apiMatchIds)) {
                 return redirect()->back()->withInput()->with('error', 'Invalid match ID submitted. Please refresh and try again.');
            }

            $predictionsToSave[] = new WeeklyBetPrediction([
                'match_id' => $predictionData['match_id'],
                'home_team_name' => $matchDetail['home']['name'],
                'away_team_name' => $matchDetail['away']['name'],
                'predicted_outcome' => $predictionData['outcome'],
                'match_utc_date_time' => Carbon::parse($matchDetail['utcDate'])->setTimezone('UTC'), // Store as UTC
            ]);
        }
        
        if (empty($predictionsToSave) || count($predictionsToSave) !== count($apiMatchIds) ) {
             Log::warning("User {$user->id} - Not enough valid predictions to save for slip ID {$slip->id}. Required: ".count($apiMatchIds).", Got: ".count($predictionsToSave));
             $slip->is_submitted = false; $slip->status = 'open'; $slip->save();
             if (!$existingSlip) { 
                WeeklyBetPrediction::where('weekly_bet_slip_id', $slip->id)->delete();
             }
             return redirect()->back()->withInput()->with('error', 'Not all predictions were processed correctly. Please try again.');
        }

        $slip->predictions()->saveMany($predictionsToSave);
        Log::info("User {$user->id} saved " . count($predictionsToSave) . " predictions for WeeklyBetSlip ID {$slip->id}");

        return redirect()->route('betting.my_bets')->with('status', 'Your weekly predictions have been submitted successfully!');
    }

    /**
     * Display the user's betting history (weekly slips).
     */
    public function myBets()
    {
        $user = Auth::user();
        // $effectiveNow = $this->getEffectiveTime(); // Could be used if 'My Bets' display logic needs it

        $slips = WeeklyBetSlip::where('user_id', $user->id)
            ->with(['predictions' => function ($query) {
                $query->orderBy('match_utc_date_time', 'asc');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('betting.my_weekly_bets', [
            'slips' => $slips,
            // 'currentTime' => $effectiveNow // If you want to pass the effective time to this view
        ]);
    }
}