<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WeeklyBetSlip;
use App\Models\GameweekBoostRequest;
use App\Services\FootballDataService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SettleWeeklyBets extends Command
{
    /**
     * The name and signature of the console command.
     * Use 'o-W' format for the week option, e.g., --week=2024-35
     */
    protected $signature = 'bets:settle-weekly {--week=}';
    
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

        $query = WeeklyBetSlip::whereIn('status', ['open', 'processing'])
            ->with('predictions');

        if ($targetWeek) {
            $query->where('week_identifier', $targetWeek);
            $this->info("Targeting specific week: {$targetWeek}");
            Log::info("SettleWeeklyBets: Targeting specific week: {$targetWeek}");
        } else {
            // -- LOGIC CHANGED --
            // We no longer limit the query to previous weeks.
            // It will now process ALL slips that are 'open' or 'processing'.
            $this->info("Processing all pending slips regardless of week.");
            Log::info("SettleWeeklyBets: Processing all pending slips (status 'open' or 'processing').");
        }

        $slipsToProcess = $query->get();

        if ($slipsToProcess->isEmpty()) {
            Log::info('SettleWeeklyBets: No slips to process currently.');
            $this->info('No weekly bet slips found needing settlement.');
            return 0;
        }

        foreach ($slipsToProcess as $slip) {
            Log::info("SettleWeeklyBets: Processing slip ID {$slip->id} for week {$slip->week_identifier}, current status: {$slip->status}");
            $this->line("Processing slip ID {$slip->id} (Week: {$slip->week_identifier}, User: {$slip->user_id}, Status: {$slip->status})");

            $allMatchesForSlipFinishedOrInvalid = true; // Assume true, set to false if any are pending
            $currentSlipTotalScore = 0;

            // Check if user has an approved Gameweek Boost for this week
            $hasGameweekBoost = GameweekBoostRequest::where('user_id', $slip->user_id)
                ->where('week_identifier', $slip->week_identifier)
                ->where('status', 'approved')
                ->exists();

            if ($hasGameweekBoost) {
                Log::info("SettleWeeklyBets: User {$slip->user_id} has APPROVED Gameweek Boost for week {$slip->week_identifier}");
            }

            if ($slip->predictions->isEmpty()) {
                Log::warning("SettleWeeklyBets: Slip ID {$slip->id} has no predictions. Marking as settled with 0 score.");
                $slip->total_score = 0;
                $slip->status = 'settled';
                $slip->save();
                continue; // Move to the next slip
            }

            foreach ($slip->predictions as $prediction) {
                // If actual_outcome is already set, it was processed previously. Recalculate score and continue.
                if ($prediction->actual_outcome !== null) {
                    $currentSlipTotalScore += $prediction->points_awarded;
                    continue;
                }

                // Fetch match details.
                $matchDetails = $this->footballDataService->getMatchDetails($prediction->match_id);

                if (!$matchDetails || !isset($matchDetails['status'])) {
                    Log::warning("SettleWeeklyBets: Could not fetch details for match ID {$prediction->match_id}. Skipping this prediction for now.");
                    $allMatchesForSlipFinishedOrInvalid = false;
                    continue;
                }
                
                Log::debug("SettleWeeklyBets: Fetched details for Match ID {$prediction->match_id}", ['details' => $matchDetails]);

                if ($matchDetails['status'] === 'FINISHED') {
                    if (!isset($matchDetails['score']['fullTime']['home']) || !isset($matchDetails['score']['fullTime']['away'])) {
                        Log::warning("SettleWeeklyBets: Match ID {$prediction->match_id} is FINISHED but scores are missing.", ['details' => $matchDetails]);
                        $allMatchesForSlipFinishedOrInvalid = false;
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

                    $prediction->actual_outcome = $actualOutcome;

                    // Calculate points: 1 point for correct, 0 for incorrect
                    // Priority: Gameweek Boost > Double Points (they don't stack)
                    if ($prediction->predicted_outcome === $actualOutcome) {
                        if ($hasGameweekBoost) {
                            // Gameweek Boost: ALL correct predictions get 2 points
                            $prediction->points_awarded = 2;
                        } elseif ($prediction->is_double_points) {
                            // Double Points: Only this specific match gets 2 points
                            $prediction->points_awarded = 2;
                        } else {
                            // Normal: 1 point
                            $prediction->points_awarded = 1;
                        }
                    } else {
                        $prediction->points_awarded = 0;
                    }

                    $prediction->save();

                    $currentSlipTotalScore += $prediction->points_awarded;

                    $logMessage = "SettleWeeklyBets: Settled prediction ID {$prediction->id}. Actual: {$actualOutcome}, Points: {$prediction->points_awarded}";
                    if ($hasGameweekBoost) {
                        $logMessage .= " (GAMEWEEK BOOST)";
                    } elseif ($prediction->is_double_points) {
                        $logMessage .= " (DOUBLE POINTS)";
                    }
                    Log::info($logMessage);

                } else if (in_array($matchDetails['status'], ['CANCELLED', 'POSTPONED'])) {
                    Log::info("SettleWeeklyBets: Match ID {$prediction->match_id} is {$matchDetails['status']}. Marking as void with 0 points.");
                    $prediction->actual_outcome = strtolower($matchDetails['status']);
                    $prediction->points_awarded = 0;
                    $prediction->save();
                
                } else {
                    // Match is still scheduled, in-play, etc.
                    Log::info("SettleWeeklyBets: Match ID {$prediction->match_id} not finished (Status: {$matchDetails['status']}).");
                    $allMatchesForSlipFinishedOrInvalid = false;
                }
            } // End foreach prediction

            if ($allMatchesForSlipFinishedOrInvalid) {
                $slip->total_score = $currentSlipTotalScore;
                $slip->status = 'settled';
                $slip->save();
                Log::info("SettleWeeklyBets: Fully settled slip ID {$slip->id}. Total Score: {$slip->total_score}");
                $this->info("-> Slip ID {$slip->id} fully settled. Total Score: {$slip->total_score}");
            } else {
                // If it's not fully settled, ensure its status is 'processing' for the next run.
                if ($slip->status !== 'processing') {
                    $slip->status = 'processing';
                    $slip->save();
                    Log::info("SettleWeeklyBets: Slip ID {$slip->id} moved to 'processing' status.");
                } else {
                    Log::info("SettleWeeklyBets: Slip ID {$slip->id} remains 'processing'.");
                }
                $this->warn("-> Slip ID {$slip->id} not fully settled. Some matches pending.");
            }
        } // End foreach slip

        Log::info('SettleWeeklyBets: Finished weekly bet settlement process.');
        $this->info('Weekly bet settlement process finished.');
        return 0;
    }
}