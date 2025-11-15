<?php

namespace App\Http\Controllers;

use App\Models\WeeklyBetSlip;
use App\Models\WeeklyBetPrediction; // Make sure this is imported
use App\Models\DoublePointRequest;
use App\Models\DoublePointWeeklyMatch;
use App\Models\GameweekBoostRequest;
use App\Services\FootballDataService;
use App\Http\Controllers\Traits\ProvidesEffectiveTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class BettingController extends Controller
{
    use ProvidesEffectiveTime;

    protected FootballDataService $footballDataService;

    public function __construct(FootballDataService $footballDataService)
    {
        $this->middleware('auth');
        $this->footballDataService = $footballDataService;
    }

    /**
     * Show the form for placing bets on the current week's matches.
     * Each match will be checked individually to see if it's still bettable.
     */
    public function showCurrentWeekBetForm()
    {
        $user = Auth::user();
        $effectiveNow = $this->getEffectiveTime();
        $effectiveNowUtc = $effectiveNow->copy()->setTimezone('UTC');

        $bettingWeekReferenceDate = $effectiveNow->copy();
        $bettingWeekIdentifier = $bettingWeekReferenceDate->format('o-W');

        $matchesResponse = $this->footballDataService->getWeeklyMatches($bettingWeekReferenceDate);

        if (!$matchesResponse['success'] || empty($matchesResponse['data'])) {
            // No matches this week - get the next upcoming gameweek
            $upcomingGameweek = $this->footballDataService->getNextGameweek($effectiveNow);

            return view('betting.weekly_bet_form', [
                'matches' => [],
                'anyMatchBettable' => false,
                'message' => $matchesResponse['message'] ?? 'No matches available for the current betting period.',
                'existingSlip' => null,
                'currentTime' => $effectiveNow,
                'weekIdentifier' => $bettingWeekIdentifier,
                'gameweek' => null, // No gameweek if no matches (e.g., international break)
                'upcomingGameweek' => $upcomingGameweek, // Show upcoming gameweek during break
                'doublePointMatch' => null,
                'existingDoublePointRequest' => null,
                'gameweekBoost' => null,
            ]);
        }

        $matches = $matchesResponse['data'];
        $anyMatchBettable = false;

        $existingSlip = WeeklyBetSlip::where('user_id', $user->id)
            ->where('week_identifier', $bettingWeekIdentifier)
            ->with('predictions')
            ->first();
        
        // --- MODIFICATION START ---
        // Get ALL match IDs the user has ever placed a bet on to prevent duplicates across weeks.
        $allUserPredictionMatchIds = WeeklyBetPrediction::whereHas('weeklyBetSlip', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->pluck('match_id');
        // --- MODIFICATION END ---

        // Process each match to determine if it's individually bettable
        foreach ($matches as $key => $match) {
            try {
                $matchTimeUtc = Carbon::parse($match['utcDate']);
                $deadline = $matchTimeUtc->copy()->subHour();
                
                // Condition 1: Is the betting window open based on time?
                $isTimeBettable = $effectiveNowUtc->lt($deadline);
                
                // --- MODIFICATION START ---
                // Condition 2: Has the user already placed a bet on this match in ANY slip?
                $hasExistingBet = $allUserPredictionMatchIds->contains($match['id']);
                // --- MODIFICATION END ---

                // A match is bettable ONLY if the time window is open AND no bet has been placed yet.
                $matches[$key]['is_bettable'] = $isTimeBettable && !$hasExistingBet;

                if ($matches[$key]['is_bettable']) {
                    $anyMatchBettable = true;
                }
            } catch (\Exception $e) {
                Log::error("Error processing match deadline for match ID {$match['id']}", ['error' => $e->getMessage()]);
                $matches[$key]['is_bettable'] = false;
            }
        }
        
        usort($matches, fn($a, $b) => strcmp($a['utcDate'], $b['utcDate']));
            
        $message = null;
        if (!$anyMatchBettable && !empty($matches)) {
             $message = 'The betting window for all available matches this week has closed, or you have already placed all your bets.';
        }

        // Get the admin-selected double point match for this week
        $doublePointMatch = DoublePointWeeklyMatch::where('week_identifier', $bettingWeekIdentifier)->first();

        // Check if user already has a double point request for this week
        $existingDoublePointRequest = null;
        if ($doublePointMatch) {
            $existingDoublePointRequest = DoublePointRequest::where('user_id', $user->id)
                ->where('week_identifier', $bettingWeekIdentifier)
                ->first();
        }

        // Check if user has a gameweek boost (they can only have one ever)
        $gameweekBoost = GameweekBoostRequest::where('user_id', $user->id)->first();

        // Get the actual gameweek number from match data (if matches exist)
        $gameweek = null;
        $upcomingGameweek = null;
        if (!empty($matches)) {
            // All matches in the same week should have the same matchday number
            $gameweek = $matches[0]['matchday'] ?? null;
        } else {
            // No matches this week - get the next upcoming gameweek
            $footballDataService = app(\App\Services\FootballDataService::class);
            $upcomingGameweek = $footballDataService->getNextGameweek($effectiveNow);
        }

        return view('betting.weekly_bet_form', [
            'matches' => $matches,
            'anyMatchBettable' => $anyMatchBettable,
            'message' => $message,
            'existingSlip' => $existingSlip,
            'weekIdentifier' => $bettingWeekIdentifier,
            'currentTime' => $effectiveNow,
            'gameweek' => $gameweek, // Pass actual gameweek from API
            'upcomingGameweek' => $upcomingGameweek, // Pass upcoming gameweek during breaks
            'doublePointMatch' => $doublePointMatch,
            'existingDoublePointRequest' => $existingDoublePointRequest,
            'gameweekBoost' => $gameweekBoost,
        ]);
    }

    /**
     * Store weekly bet predictions. A bet for a specific match is now permanent once made.
     * This functions as a "Save" for new, un-betted matches only.
     */
    public function storeCurrentWeekBet(Request $request)
    {
        $user = Auth::user();
        $effectiveNow = $this->getEffectiveTime();
        $effectiveNowUtc = $effectiveNow->copy()->setTimezone('UTC');

        $bettingWeekReferenceDate = $effectiveNow->copy();
        $bettingWeekIdentifier = $bettingWeekReferenceDate->format('o-W');

        Log::debug('--- Begin Bet Submission ---', [
            'user_id' => $user->id,
            'effective_time_utc' => $effectiveNowUtc->toIso8601String(),
            'week_identifier' => $bettingWeekIdentifier,
            'payload' => $request->all(),
        ]);

        $submittedPredictions = $request->input('predictions', []);
        $completePredictions = collect($submittedPredictions)->filter(function ($prediction) {
            return isset($prediction['match_id']) && isset($prediction['outcome']);
        })->values()->all();

        Log::debug('Filtered complete predictions from payload.', ['complete_predictions' => $completePredictions]);

        $request->merge(['predictions' => $completePredictions]);
        $request->validate([
            'predictions' => 'sometimes|array',
            'predictions.*.match_id' => 'required|integer',
            'predictions.*.outcome' => 'required|in:home_win,draw,away_win',
            'opt_in_double_points' => 'nullable|boolean',
        ]);

        if (empty($completePredictions)) {
            Log::warning('Submission failed: No complete predictions were submitted.');
            return redirect()->back()->with('error', 'You did not select an outcome for any match.');
        }

        $matchesResponse = $this->footballDataService->getWeeklyMatches($bettingWeekReferenceDate);
        if (!$matchesResponse['success'] || empty($matchesResponse['data'])) {
            Log::error('Submission failed: Could not retrieve match data from FootballDataService.');
            return redirect()->route('betting.show_form')->with('error', 'Could not retrieve match data to validate predictions. Please try again.');
        }

        $allWeeklyMatches = collect($matchesResponse['data']);
        $matchTimes = $allWeeklyMatches->keyBy('id')->map(fn($match) => $match['utcDate']);

        $slip = WeeklyBetSlip::firstOrCreate(
            ['user_id' => $user->id, 'week_identifier' => $bettingWeekIdentifier],
            ['status' => 'open', 'is_submitted' => false,
            'betting_opens_at' => null]
        );

        // --- MODIFICATION START ---
        // Fetch all match IDs the user has ever bet on to prevent duplicates.
        $allUserPredictionMatchIds = WeeklyBetPrediction::whereHas('weeklyBetSlip', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->pluck('match_id');
        // --- MODIFICATION END ---

        $predictionsSavedCount = 0;
        $lockedMatchesAttempted = [];

        Log::debug('Processing submitted predictions...', ['count' => count($completePredictions)]);
        foreach ($completePredictions as $predictionData) {
            $matchId = $predictionData['match_id'];

            if (!$matchTimes->has($matchId)) {
                Log::warning("Skipping prediction: Match ID {$matchId} not found in this week's match set.", ['user_id' => $user->id]);
                continue;
            }

            $matchTimeUtc = Carbon::parse($matchTimes->get($matchId));
            $deadline = $matchTimeUtc->copy()->subHour();

            $isTimeLocked = $effectiveNowUtc->gte($deadline);

            // --- MODIFICATION START ---
            // Check if a bet for this match exists in ANY of the user's slips.
            $isAlreadyBet = $allUserPredictionMatchIds->contains($matchId);
            // --- MODIFICATION END ---

            Log::debug("Checking lock status for Match ID {$matchId}", [
                'is_time_locked' => $isTimeLocked,
                'is_already_bet' => $isAlreadyBet,
            ]);

            // Save a prediction only if the match is NOT time-locked AND NOT already bet on.
            if (!$isTimeLocked && !$isAlreadyBet) {
                $matchDetail = $allWeeklyMatches->firstWhere('id', $matchId);
                WeeklyBetPrediction::create([
                    'weekly_bet_slip_id' => $slip->id,
                    'match_id' => $matchId,
                    'home_team_name' => $matchDetail['home']['name'],
                    'away_team_name' => $matchDetail['away']['name'],
                    'predicted_outcome' => $predictionData['outcome'],
                    'match_utc_date_time' => $matchTimeUtc,
                ]);
                $predictionsSavedCount++;
                Log::info("Prediction for Match ID {$matchId} was VALID and CREATED.", ['user_id' => $user->id]);
            } else {
                // Match is locked (either by time or because a bet already exists)
                $matchDetails = $allWeeklyMatches->firstWhere('id', $matchId);
                $lockedMatchesAttempted[] = ($matchDetails['home']['name'] ?? 'Team') . ' vs ' . ($matchDetails['away']['name'] ?? 'Team');
                $reason = $isTimeLocked ? 'deadline passed' : 'already bet on';
                Log::warning("Prediction for Match ID {$matchId} was REJECTED ({$reason}).", ['user_id' => $user->id]);
            }
        }

        Log::debug('Finished processing predictions.', [
            'valid_predictions_saved' => $predictionsSavedCount,
            'locked_matches_attempted' => $lockedMatchesAttempted,
        ]);

        if ($predictionsSavedCount === 0 && !empty($lockedMatchesAttempted)) {
            $errorMessage = 'No new predictions were saved. The betting window for your selected matches has closed or you have already placed a bet on them.';
            if (!empty($lockedMatchesAttempted)) {
                 $errorMessage .= ' (' . implode(', ', array_unique($lockedMatchesAttempted)) . ')';
            }
            Log::error('Submission failed: No valid predictions to save.', ['user_id' => $user->id, 'error' => $errorMessage]);
            return redirect()->back()->withInput()->with('error', $errorMessage);
        }
        
        if ($predictionsSavedCount === 0 && empty($lockedMatchesAttempted)) {
             return redirect()->back()->withInput()->with('error', 'You did not make any new predictions.');
        }

        $successMessage = "Successfully saved {$predictionsSavedCount} new prediction(s)!";
        if (!empty($lockedMatchesAttempted)) {
            $successMessage .= ' Note: Predictions for some matches were ignored as they are now locked (' . implode(', ', array_unique($lockedMatchesAttempted)) . ').';
        }

        // Handle Double Point Opt-In
        $optInDoublePoints = $request->input('opt_in_double_points', false);
        if ($optInDoublePoints) {
            // Check if user has an approved Gameweek Boost (powers don't stack)
            $approvedBoost = GameweekBoostRequest::where('user_id', $user->id)
                ->where('status', 'approved')
                ->first();

            if ($approvedBoost) {
                Log::warning('User attempted to opt-in for double points but has an approved Gameweek Boost', [
                    'user_id' => $user->id,
                    'week' => $bettingWeekIdentifier
                ]);
                // Skip double point opt-in silently (user shouldn't see this option anyway due to UI logic)
            } else {
                // Get the admin-selected double point match for this week
                $doublePointMatch = DoublePointWeeklyMatch::where('week_identifier', $bettingWeekIdentifier)->first();

                if ($doublePointMatch) {
                    // Check if user already has a double point request for this week
                    $existingRequest = DoublePointRequest::where('user_id', $user->id)
                        ->where('week_identifier', $bettingWeekIdentifier)
                        ->first();

                    if (!$existingRequest) {
                    // Find the prediction for the admin-selected double point match
                    $prediction = WeeklyBetPrediction::where('weekly_bet_slip_id', $slip->id)
                        ->where('match_id', $doublePointMatch->match_id)
                        ->first();

                    if ($prediction) {
                        DoublePointRequest::create([
                            'user_id' => $user->id,
                            'weekly_bet_prediction_id' => $prediction->id,
                            'week_identifier' => $bettingWeekIdentifier,
                            'status' => 'pending',
                        ]);

                        $successMessage .= ' Your Double Points opt-in has been submitted for admin approval!';
                        Log::info('Double point opt-in created', [
                            'user_id' => $user->id,
                            'match_id' => $doublePointMatch->match_id,
                            'week' => $bettingWeekIdentifier
                        ]);
                    }
                    } else {
                        Log::warning('User already has a double point request for this week', [
                            'user_id' => $user->id,
                            'week' => $bettingWeekIdentifier
                        ]);
                    }
                }
            }
        }

        // Handle Gameweek Boost Activation
        $activateBoost = $request->input('activate_boost', false);
        if ($activateBoost) {
            // Check if user already has a boost (they can only have one ever)
            $existingBoost = GameweekBoostRequest::where('user_id', $user->id)->first();

            if (!$existingBoost) {
                GameweekBoostRequest::create([
                    'user_id' => $user->id,
                    'week_identifier' => $bettingWeekIdentifier,
                    'status' => 'pending',
                ]);

                $successMessage .= ' Your Gameweek Boost has been submitted for admin approval!';
                Log::info('Gameweek boost request created', [
                    'user_id' => $user->id,
                    'week' => $bettingWeekIdentifier
                ]);
            } else {
                Log::warning('User already has a gameweek boost request', [
                    'user_id' => $user->id,
                    'existing_week' => $existingBoost->week_identifier,
                    'status' => $existingBoost->status
                ]);
            }
        }

        Log::info('Submission successful.', ['user_id' => $user->id, 'message' => $successMessage]);
        Log::debug('--- End Bet Submission ---');
        return redirect()->route('betting.show_form')->with('status', $successMessage);
    }

    /**
     * Display the user's betting history (weekly slips).
     */
    public function myBets()
    {
        $user = Auth::user();
        $slips = WeeklyBetSlip::where('user_id', $user->id)
            ->with([
                'predictions' => fn($query) => $query->orderBy('match_utc_date_time', 'asc'),
                'predictions.doublePointRequest'
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Fetch actual gameweek numbers for each slip by looking up match data from API (cached)
        foreach ($slips as $slip) {
            $slip->actual_gameweek = null;

            // Get the first prediction's match to determine the gameweek
            $firstPrediction = $slip->predictions->first();
            if ($firstPrediction && $firstPrediction->match_id) {
                try {
                    $matchDetails = $this->footballDataService->getMatchDetails($firstPrediction->match_id);
                    if ($matchDetails && isset($matchDetails['matchday'])) {
                        $slip->actual_gameweek = $matchDetails['matchday'];
                    }
                } catch (\Exception $e) {
                    // If API fails, gameweek will remain null and fall back to week identifier
                    Log::warning("Failed to fetch gameweek for slip {$slip->id}", ['error' => $e->getMessage()]);
                }
            }
        }

        return view('betting.my_weekly_bets', [
            'slips' => $slips,
        ]);
    }
}