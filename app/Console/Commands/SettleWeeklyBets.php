<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WeeklyBetSlip;
use App\Models\WeeklyBetPrediction; // Ensure this is used if needed, though not directly in this version
use App\Services\FootballDataService;
use Carbon\Carbon; // Ensure this is used if needed, though not directly in this version
use Illuminate\Support\Facades\Log;

class SettleWeeklyBets extends Command
{
    protected $signature = 'bets:settle-weekly {--week=}'; // Added optional week argument for targeted settlement
    protected $description = 'Settles weekly bets by fetching match results and updating scores.';
    protected FootballDataService $footballDataService;

    public function __construct(FootballDataService $footballDataService)
    {
        parent::__construct();
        $this->footballDataService = $footballDataService;
    }

    public function handle()
    {
        Log::info('SettleWeeklyBets: Starting weekly bet settlement process.');
        $this->info('Starting weekly bet settlement process...');

        $targetWeek = $this->option('week');

        $query = WeeklyBetSlip::where('is_submitted', true)
            // ---- MODIFIED HERE ----
            ->whereIn('status', ['submitted', 'processing']) // Now includes 'processing' slips
            ->with('predictions');

        if ($targetWeek) {
            $query->where('week_identifier', $targetWeek);
            $this->info("Targeting week: {$targetWeek}");
            Log::info("SettleWeeklyBets: Targeting specific week: {$targetWeek}");
        } else {
            // Optional: Add a condition to only process slips whose betting_closes_at is in the past
            // to avoid trying to settle slips for a future "effective week" from controller testing.
            // $query->where('betting_closes_at', '<=', Carbon::now('UTC'));
            // However, FootballDataService fetches based on actual Carbon::now()->subWeeks(1),
            // so slips created by controller will likely be for past betting_closes_at anyway.
        }

        $slipsToProcess = $query->get();

        if ($slipsToProcess->isEmpty()) {
            Log::info('SettleWeeklyBets: No slips to process currently (status submitted or processing).');
            $this->info('No weekly bet slips found needing settlement (status submitted or processing).');
            return 0;
        }

        foreach ($slipsToProcess as $slip) {
            Log::info("SettleWeeklyBets: Processing slip ID {$slip->id} for week {$slip->week_identifier}, current status: {$slip->status}");
            $this->line("Processing slip ID {$slip->id} (Week: {$slip->week_identifier}, User: {$slip->user_id}, Status: {$slip->status})");

            $allMatchesForSlipFinishedOrInvalid = true; // Assume true, set to false if any are pending
            $currentSlipTotalScore = 0;

            if ($slip->predictions->isEmpty()) {
                Log::warning("SettleWeeklyBets: Slip ID {$slip->id} has no predictions. Marking as settled with 0 score if it's not already.");
                if($slip->status !== 'settled') {
                    $slip->total_score = 0;
                    $slip->status = 'settled'; // Or a different status like 'error_no_predictions'
                    $slip->save();
                }
                continue; // Move to the next slip
            }

            foreach ($slip->predictions as $prediction) {
                // If actual_outcome is already set, it means this prediction was processed in a previous run.
                if ($prediction->actual_outcome !== null) {
                    $currentSlipTotalScore += $prediction->points_awarded;
                    Log::debug("SettleWeeklyBets: Prediction ID {$prediction->id} (Match ID {$prediction->match_id}) already processed. Actual: {$prediction->actual_outcome}, Points: {$prediction->points_awarded}");
                    continue; // Skip to the next prediction
                }

                // Fetch match details. FootballDataService::getMatchDetails caches results.
                $matchDetails = $this->footballDataService->getMatchDetails($prediction->match_id);

                if (!$matchDetails || !isset($matchDetails['status'])) {
                    Log::warning("SettleWeeklyBets: Could not fetch details for match ID {$prediction->match_id} for slip {$slip->id}. Skipping this prediction for now. Match will remain pending.");
                    $allMatchesForSlipFinishedOrInvalid = false; // Can't settle slip if a match detail is missing
                    continue;
                }
                
                Log::debug("SettleWeeklyBets: Fetched details for Match ID {$prediction->match_id}", ['details' => $matchDetails]);

                if ($matchDetails['status'] === 'FINISHED') {
                    // Ensure score and fullTime keys exist, as seen in your API sample for getMatchDetails
                    if (!isset($matchDetails['score']['fullTime']['home']) || !isset($matchDetails['score']['fullTime']['away'])) {
                         Log::warning("SettleWeeklyBets: Match ID {$prediction->match_id} is FINISHED but score->fullTime->home/away scores are missing. Cannot settle this prediction.", ['details' => $matchDetails]);
                         $allMatchesForSlipFinishedOrInvalid = false; // Cannot be certain about this match
                         continue;
                    }
                    $actualHomeScore = $matchDetails['score']['fullTime']['home'];
                    $actualAwayScore = $matchDetails['score']['fullTime']['away'];

                    $actualOutcome = 'draw';
                    if ($actualHomeScore > $actualAwayScore) {
                        $actualOutcome = 'home_win';
                    } elseif ($actualAwayScore > $actualHomeScore) {
                        $actualOutcome = 'away_win';
                    }

                    $prediction->actual_outcome = $actualOutcome; // Mark as processed
                    if ($prediction->predicted_outcome === $actualOutcome) {
                        $prediction->points_awarded = 1;
                    } else {
                        $prediction->points_awarded = 0;
                    }
                    $prediction->save();
                    $currentSlipTotalScore += $prediction->points_awarded;
                    Log::info("SettleWeeklyBets: Settled prediction ID {$prediction->id} (Match ID {$prediction->match_id}). Actual: {$actualOutcome}, Predicted: {$prediction->predicted_outcome}, Points: {$prediction->points_awarded}");

                } else if (in_array($matchDetails['status'], ['CANCELLED', 'POSTPONED'])) {
                    Log::info("SettleWeeklyBets: Match ID {$prediction->match_id} is {$matchDetails['status']}. Marking as processed with 0 points (or your specific rule).");
                    $prediction->actual_outcome = strtolower($matchDetails['status']); // e.g., 'cancelled', 'postponed'
                    $prediction->points_awarded = 0; // Or apply specific rules for cancelled/postponed
                    $prediction->save();
                    // $currentSlipTotalScore += $prediction->points_awarded; // If cancelled matches give points
                    // $allMatchesForSlipFinishedOrInvalid remains true as this match is now "handled"
                } else {
                    // For any other status (SCHEDULED, IN_PLAY, PAUSED, SUSPENDED, etc.)
                    Log::info("SettleWeeklyBets: Match ID {$prediction->match_id} is not yet finished (Status: {$matchDetails['status']}). Will retry next time.");
                    $allMatchesForSlipFinishedOrInvalid = false; // Not all matches are in a final state
                }
            } // End foreach prediction

            if ($allMatchesForSlipFinishedOrInvalid) {
                $slip->total_score = $currentSlipTotalScore;
                $slip->status = 'settled';
                $slip->save();
                Log::info("SettleWeeklyBets: Fully settled slip ID {$slip->id}. Total Score: {$slip->total_score}");
                $this->info("Slip ID {$slip->id} fully settled. Total Score: {$slip->total_score}");
            } else {
                // If it's not fully settled but was 'submitted', it should now be 'processing'.
                // If it was already 'processing', it remains 'processing'.
                if ($slip->status === 'submitted') {
                     $slip->status = 'processing';
                     $slip->save();
                     Log::info("SettleWeeklyBets: Slip ID {$slip->id} moved to 'processing' status as some matches are pending.");
                } else {
                    Log::info("SettleWeeklyBets: Slip ID {$slip->id} remains in '{$slip->status}' status as some matches are still pending final state.");
                }
                $this->info("Slip ID {$slip->id} not yet fully settled. Some matches pending.");
            }
        } // End foreach slip

        Log::info('SettleWeeklyBets: Finished weekly bet settlement process.');
        $this->info('Weekly bet settlement process finished.');
        return 0;
    }
}