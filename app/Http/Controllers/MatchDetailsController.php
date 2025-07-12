<?php

namespace App\Http\Controllers;

use App\Services\FootballDataService;

class MatchDetailsController extends Controller
{
    public function __construct(protected FootballDataService $footballDataService)
    {
        $this->middleware('auth');
    }

    /**
     * Show the Blade view for a single match. 404 if not found.
     */
    public function show(int $matchId)
    {
        $match = $this->footballDataService->getMatchDetails($matchId);

        if (! $match) {
            abort(404);
        }

        return view('match_details', ['match' => $match]);
    }
}
