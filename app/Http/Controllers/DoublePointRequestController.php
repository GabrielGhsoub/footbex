<?php

namespace App\Http\Controllers;

use App\Models\DoublePointRequest;
use App\Models\WeeklyBetPrediction;
use App\Models\DoublePointWeeklyMatch;
use App\Services\FootballDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DoublePointRequestController extends Controller
{
    protected FootballDataService $footballDataService;

    public function __construct(FootballDataService $footballDataService)
    {
        $this->middleware('auth');
        $this->footballDataService = $footballDataService;
    }

    /**
     * Submit a double point request for a prediction
     */
    public function store(Request $request)
    {
        $request->validate([
            'prediction_id' => 'required|exists:weekly_bet_predictions,id',
        ]);

        $user = Auth::user();
        $prediction = WeeklyBetPrediction::with('weeklyBetSlip')->findOrFail($request->prediction_id);

        // Verify this prediction belongs to the current user
        if ($prediction->weeklyBetSlip->user_id !== $user->id) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        // Get the week identifier from the slip
        $weekIdentifier = $prediction->weeklyBetSlip->week_identifier;

        // Extract gameweek number from week identifier (format: YYYY-WW)
        $gameweek = (int) explode('-', $weekIdentifier)[1];

        // Check if it's gameweek 20 or later
        if ($gameweek < 20) {
            return redirect()->back()->with('error', 'Double points feature is only available from gameweek 20 onwards.');
        }

        // Check if user already has a double point request for this week
        $existingRequest = DoublePointRequest::where('user_id', $user->id)
            ->where('week_identifier', $weekIdentifier)
            ->first();

        if ($existingRequest) {
            return redirect()->back()->with('error', 'You already have a double point request for this week.');
        }

        // Create the request
        DoublePointRequest::create([
            'user_id' => $user->id,
            'weekly_bet_prediction_id' => $prediction->id,
            'week_identifier' => $weekIdentifier,
            'match_id' => $prediction->match_id,
            'status' => 'pending',
        ]);

        Log::info("Double point request created", [
            'user_id' => $user->id,
            'prediction_id' => $prediction->id,
            'week' => $weekIdentifier,
        ]);

        return redirect()->back()->with('status', 'Double point request submitted! Waiting for admin approval.');
    }

    /**
     * Admin: View all double point requests
     */
    public function index()
    {
        $requests = DoublePointRequest::with(['user', 'prediction', 'approver'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.double_point_requests', compact('requests'));
    }

    /**
     * Admin: Approve a double point request
     */
    public function approve($id)
    {
        $doublePointRequest = DoublePointRequest::findOrFail($id);

        if (!$doublePointRequest->isPending()) {
            return redirect()->back()->with('error', 'This request has already been processed.');
        }

        $doublePointRequest->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        // Mark the prediction as double points
        $doublePointRequest->prediction->update([
            'is_double_points' => true,
        ]);

        Log::info("Double point request approved", [
            'request_id' => $id,
            'approved_by' => Auth::id(),
            'user_id' => $doublePointRequest->user_id,
        ]);

        return redirect()->back()->with('status', 'Double point request approved!');
    }

    /**
     * Admin: Reject a double point request
     */
    public function reject(Request $request, $id)
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
            'reason' => $request->rejection_reason,
        ]);

        return redirect()->back()->with('status', 'Double point request rejected.');
    }

    /**
     * Admin: Show unified double point management page
     */
    public function showWeeklyMatchSelection()
    {
        // Get current week or use from request
        $weekIdentifier = request('week', now()->format('o-W'));

        // Check if match already set for this week
        $existingMatch = DoublePointWeeklyMatch::where('week_identifier', $weekIdentifier)->first();

        // Fetch matches for the current week using FootballDataService
        $matchesResponse = $this->footballDataService->getWeeklyMatches(now());

        $matches = [];
        $message = null;

        if (!$matchesResponse['success'] || empty($matchesResponse['data'])) {
            $message = $matchesResponse['message'] ?? 'No matches available for the current week.';
        } else {
            $matches = $matchesResponse['data'];
            // Sort matches by date
            usort($matches, fn($a, $b) => strcmp($a['utcDate'], $b['utcDate']));
        }

        // Get all double point requests for this week
        $requests = DoublePointRequest::with(['user', 'prediction'])
            ->where('week_identifier', $weekIdentifier)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.double_point_management', compact('existingMatch', 'weekIdentifier', 'matches', 'message', 'requests'));
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

        // Update or create the weekly match
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
}
