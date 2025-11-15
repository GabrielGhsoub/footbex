<?php

namespace App\Http\Controllers;

use App\Models\DoublePointRequest;
use App\Models\DoublePointWeeklyMatch;
use App\Models\GameweekBoostRequest;
use App\Services\FootballDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PowerUpsController extends Controller
{
    use \App\Http\Controllers\Traits\ProvidesEffectiveTime;

    protected FootballDataService $footballDataService;

    public function __construct(FootballDataService $footballDataService)
    {
        $this->middleware('auth');
        $this->footballDataService = $footballDataService;
    }

    /**
     * Admin: Show unified power-ups management page
     */
    public function index()
    {
        // Get current week or use from request (use effective time for testing)
        $effectiveNow = $this->getEffectiveTime();
        $weekIdentifier = request('week', $effectiveNow->format('o-W'));

        // Check if double point match already set for this week
        $existingMatch = DoublePointWeeklyMatch::where('week_identifier', $weekIdentifier)->first();

        // Fetch matches for the current week (use effective time for testing)
        $matchesResponse = $this->footballDataService->getWeeklyMatches($effectiveNow);

        $matches = [];
        $message = null;

        if (!$matchesResponse['success'] || empty($matchesResponse['data'])) {
            $message = $matchesResponse['message'] ?? 'No matches available for the current week.';
        } else {
            $matches = $matchesResponse['data'];
            usort($matches, fn($a, $b) => strcmp($a['utcDate'], $b['utcDate']));
        }

        // Get double point opt-in requests for this week
        $doublePointRequests = DoublePointRequest::with(['user', 'prediction'])
            ->where('week_identifier', $weekIdentifier)
            ->orderBy('created_at', 'desc')
            ->get();

        // Get all gameweek boost requests (not filtered by week)
        $boostRequests = GameweekBoostRequest::with(['user', 'approver'])
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
            ->orderBy('created_at', 'desc')
            ->get();

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

        return view('admin.powerups', compact(
            'existingMatch',
            'weekIdentifier',
            'gameweek',
            'upcomingGameweek',
            'matches',
            'message',
            'doublePointRequests',
            'boostRequests'
        ));
    }

    /**
     * Admin: Set the double point match for a week
     */
    public function setWeeklyMatch(Request $request)
    {
        $request->validate([
            'week_identifier' => 'required|string',
            'match_id' => 'required|integer',
            'home_team_name' => 'required|string',
            'away_team_name' => 'required|string',
        ]);

        DoublePointWeeklyMatch::updateOrCreate(
            ['week_identifier' => $request->week_identifier],
            [
                'match_id' => $request->match_id,
                'home_team_name' => $request->home_team_name,
                'away_team_name' => $request->away_team_name,
                'set_by' => Auth::id(),
            ]
        );

        Log::info('Double point weekly match set', [
            'week' => $request->week_identifier,
            'match_id' => $request->match_id,
            'set_by' => Auth::id(),
        ]);

        return redirect()->back()->with('status', 'Double point match set successfully!');
    }

    /**
     * Admin: Approve a double point opt-in request
     */
    public function approveDoublePoint($id)
    {
        $request = DoublePointRequest::findOrFail($id);

        if (!$request->isPending()) {
            return redirect()->back()->with('error', 'This request has already been processed.');
        }

        $request->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        $request->prediction->update([
            'is_double_points' => true,
        ]);

        Log::info("Double point request approved", [
            'request_id' => $id,
            'approved_by' => Auth::id(),
        ]);

        return redirect()->back()->with('status', 'Double point request approved!');
    }

    /**
     * Admin: Reject a double point opt-in request
     */
    public function rejectDoublePoint(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $doublePointRequest = DoublePointRequest::findOrFail($id);

        if (!$doublePointRequest->isPending()) {
            return redirect()->back()->with('error', 'This request has already been processed.');
        }

        $doublePointRequest->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        Log::info("Double point request rejected", [
            'request_id' => $id,
            'rejected_by' => Auth::id(),
        ]);

        return redirect()->back()->with('status', 'Double point request rejected.');
    }

    /**
     * Admin: Approve a gameweek boost request
     */
    public function approveBoost($id)
    {
        $request = GameweekBoostRequest::findOrFail($id);

        if (!$request->isPending()) {
            return redirect()->back()->with('error', 'This request has already been processed.');
        }

        $request->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        Log::info("Gameweek boost request approved", [
            'request_id' => $id,
            'user_id' => $request->user_id,
            'week' => $request->week_identifier,
            'approved_by' => Auth::id(),
        ]);

        return redirect()->back()->with('status', 'Gameweek boost request approved!');
    }

    /**
     * Admin: Reject a gameweek boost request
     */
    public function rejectBoost(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $boostRequest = GameweekBoostRequest::findOrFail($id);

        if (!$boostRequest->isPending()) {
            return redirect()->back()->with('error', 'This request has already been processed.');
        }

        $boostRequest->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        Log::info("Gameweek boost request rejected", [
            'request_id' => $id,
            'rejected_by' => Auth::id(),
        ]);

        return redirect()->back()->with('status', 'Gameweek boost request rejected.');
    }
}
