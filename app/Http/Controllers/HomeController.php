<?php

namespace App\Http\Controllers;

use App\Services\FootballDataService;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\WeeklyBetSlip;
use App\Http\Controllers\Traits\ProvidesEffectiveTime;
use Illuminate\Support\Facades\DB;
use App\Models\Setting; // <-- ADD THIS LINE

class HomeController extends Controller
{
    use ProvidesEffectiveTime;

    protected FootballDataService $footballDataService;

    public function __construct(FootballDataService $footballDataService)
    {
        $this->footballDataService = $footballDataService;
    }

    /**
     * Show the main welcome dashboard with leaderboard.
     */
    public function showWelcomeDashboard()
    {
        $leaderboardUsers = User::select('users.id', 'users.name')
            ->addSelect(DB::raw('SUM(COALESCE(weekly_bet_predictions.points_awarded, 0)) as total_points'))
            ->leftJoin('weekly_bet_slips', 'users.id', '=', 'weekly_bet_slips.user_id')
            ->leftJoin('weekly_bet_predictions', 'weekly_bet_slips.id', '=', 'weekly_bet_predictions.weekly_bet_slip_id')
            ->groupBy('users.id', 'users.name')
            ->orderBy('total_points', 'desc')
            ->orderBy('users.name', 'asc')
            ->get();

        // Fetches the pool prize from the database, with a default fallback.
        $poolPrize = Setting::getValue('pool_size', '1000');

        return view('welcome', [
            'leaderboardUsers' => $leaderboardUsers,
            'poolPrize' => $poolPrize,
        ]);
    }

    /**
     * Your existing dashboard method (if used by another route like /dashboard).
     */
    public function dashboard()
    {
        return view('dashboard');
    }

    /**
     * API endpoint to fetch weekly matches JSON.
     */
    public function index()
    {
        Log::info("HomeController: Request received for weekly matches API (index method).");

        $effectiveNow = $this->getEffectiveTime(); 
        
        // FIX: Use the week of the effective date, not the week before it.
        $referenceDate = $effectiveNow->copy();

        Log::debug('HomeController: using effective time to fetch matches.', [
            'effective_time' => $effectiveNow->toDateTimeString(),
            'reference_date_for_service' => $referenceDate->toDateTimeString()
        ]);
        
        $output = $this->footballDataService->getWeeklyMatches($referenceDate); 

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