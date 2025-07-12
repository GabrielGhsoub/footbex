<?php

namespace App\Http\Controllers;

use App\Services\FootballDataService;
use Illuminate\Support\Facades\Log;
use App\Models\User; // Import User model
use App\Models\WeeklyBetSlip; // Import WeeklyBetSlip model
use Illuminate\Support\Facades\DB; // Import DB facade for sum if needed, or use Eloquent aggregate

class HomeController extends Controller
{
    protected FootballDataService $footballDataService;

    public function __construct(FootballDataService $footballDataService)
    {
        $this->footballDataService = $footballDataService;
        // Auth middleware is handled by the route group
    }

    /**
     * Show the main welcome dashboard with leaderboard.
     */
    public function showWelcomeDashboard()
    {
        // --- Leaderboard Logic ---
        // Fetch users with their total scores from 'settled' weekly bet slips
        $leaderboardUsers = User::select('users.id', 'users.name')
            ->addSelect(DB::raw('SUM(CASE WHEN weekly_bet_slips.status = "settled" THEN weekly_bet_slips.total_score ELSE 0 END) as total_points'))
            ->leftJoin('weekly_bet_slips', 'users.id', '=', 'weekly_bet_slips.user_id')
            ->groupBy('users.id', 'users.name')
            ->orderBy('total_points', 'desc')
            ->orderBy('users.name', 'asc') // Secondary sort for ties
            ->take(10) // Get top 10 users, for example
            ->get();

        // You can also use Eloquent's withSum for a cleaner approach if relationships are perfectly set up,
        // but the raw query gives fine-grained control over the 'settled' condition for summing.
        // Example with withSum (ensure User model has weeklyBetSlips relationship):
        // $leaderboardUsers = User::withSum(['weeklyBetSlips' => function ($query) {
        //     $query->where('status', 'settled');
        // }], 'total_score')
        // ->orderBy('weekly_bet_slips_sum_total_score', 'desc')
        // ->take(10)
        // ->get();
        // // You'd then access the sum via $user->weekly_bet_slips_sum_total_score


        // Hardcoded Pool Prize
        $poolPrize = 1000; // Example: $1000. You can make this dynamic or configurable later.

        // --- Other Dashboard Data (Placeholder - from your original welcome.blade.php) ---
        // You might fetch matches for today or other stats here if needed.
        // For now, focusing on the leaderboard.
        // $matchesToday = []; // Example: $this->footballDataService->getMatchesForDate(Carbon::today());

        return view('welcome', [
            'leaderboardUsers' => $leaderboardUsers,
            'poolPrize' => $poolPrize,
            // 'matchesToday' => $matchesToday, // Pass other data if you fetch it
        ]);
    }


    /**
     * Your existing dashboard method (if used by another route like /dashboard).
     * If '/' is your only dashboard, this method might become redundant
     * or be merged with showWelcomeDashboard.
     */
    public function dashboard()
    {
        // This might be for a different dashboard view or can be removed if
        // showWelcomeDashboard is the primary dashboard.
        return view('dashboard'); // Assumes you have a dashboard.blade.php
    }


    /**
     * API endpoint to fetch weekly matches JSON.
     */
    public function index()
    {
        Log::info("HomeController: Request received for weekly matches API (index method).");
        // Use the FootballDataService to get matches for the current betting week
        // (which is Carbon::now()->subWeeks(1) based on your service)
        $output = $this->footballDataService->getWeeklyMatches(); // Assuming this is what you need for an API

        if ($output['success']) {
            Log::info("HomeController: Successfully returning weekly matches data.");
            return response()->json($output);
        } else {
            Log::error("HomeController: Failed to get weekly matches - " . ($output['message'] ?? 'Unknown service error'));
            return response()->json([
                'success' => false,
                'message' => $output['message'] ?? 'Failed to load fixtures data.',
                'data' => [],
            ], 500);
        }
    }

    /**
     * Show the match details page. (Likely unused if MatchDetailsController handles this)
     */
     public function showMatchDetails(int $matchId)
     {
         return view('match-details', ['matchId' => $matchId]);
     }
}