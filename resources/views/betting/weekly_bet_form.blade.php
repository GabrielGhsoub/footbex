@extends('adminlte::page')

@section('title', 'Weekly Premier League Bets')

@section('content_header')
    <h1><i class="fas fa-futbol mr-2"></i>Premier League - Bet on This Week's Matches</h1>
@stop

@section('content')
    <div class="card card-primary card-outline">
        <div class="card-header responsive-header">
            @php
                // Temporary UI-only gameweek calculation
                $gameweek = null;
                $startYear = 2025;
                $startWeekOfYear = 33; // Week 33 of 2025 is Gameweek 1

                if (isset($weekIdentifier) && str_contains($weekIdentifier, '-')) {
                    list($slipYear, $slipWeek) = array_map('intval', explode('-', $weekIdentifier));

                    if ($slipYear === $startYear && $slipWeek >= $startWeekOfYear) {
                        $gameweek = ($slipWeek - $startWeekOfYear) + 1;
                    } elseif ($slipYear > $startYear) {
                        // Handle rollover to the next calendar year
                        $gameweek = (52 - $startWeekOfYear) + 1 + $slipWeek;
                    }
                }
            @endphp

            <h3 class="card-title mb-1 mb-md-0">
                <i class="fas fa-calendar-week mr-2"></i>
                @if($gameweek)
                    Gameweek {{ $gameweek }}
                @else
                    Week: {{ $weekIdentifier ?? 'N/A' }} {{-- Fallback for pre-season slips --}}
                @endif
            </h3>
        </div>
        <div class="card-body">
            {{-- Session Messages --}}
            @if (session('status'))
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <i class="icon fas fa-check"></i>{{ session('status') }}
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <i class="icon fas fa-ban"></i>{{ session('error') }}
                </div>
            @endif
            @if ($message)
                <div class="alert alert-warning"><i class="icon fas fa-exclamation-triangle"></i>{{ $message }}</div>
            @endif

            {{-- Main Betting Form --}}
            {{-- Show the form only if there are matches and at least one is bettable --}}
            @if ($anyMatchBettable && !$existingSlip?->is_submitted)
                <form method="POST" action="{{ route('betting.store') }}">
                    @csrf

                    {{-- DESKTOP VIEW: TABLE --}}
                    <div class="d-none d-lg-block">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover betting-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 20%;">Date & Time (UTC)</th>
                                        <th>Match</th>
                                        <th style="width: 10%;">Home Win</th>
                                        <th style="width: 10%;">Draw</th>
                                        <th style="width: 10%;">Away Win</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($matches as $index => $match)
                                        @php
                                            $existingPrediction = $existingSlip?->predictions->firstWhere('match_id', $match['id']);
                                            $isBettable = $match['is_bettable'] && !$existingSlip?->is_submitted;
                                        @endphp
                                        <tr class="{{ !$isBettable ? 'match-locked' : '' }}">
                                            <td>
                                                {{ \Carbon\Carbon::parse($match['utcDate'])->format('D, M j H:i') }}
                                                @if (!$isBettable)
                                                    <span class="badge badge-danger ml-2">Locked</span>
                                                @endif
                                                <input type="hidden" name="predictions[{{ $index }}][match_id]" value="{{ $match['id'] }}">
                                            </td>
                                            <td class="match-cell">
                                                <img src="{{ $match['home']['crest'] ?? asset('images/default_crest.png') }}" alt="{{ $match['home']['name'] }}" class="team-crest">
                                                <span class="team-name">{{ $match['home']['name'] }}</span>
                                                <span class="vs-separator">vs</span>
                                                <span class="team-name">{{ $match['away']['name'] }}</span>
                                                <img src="{{ $match['away']['crest'] ?? asset('images/default_crest.png') }}" alt="{{ $match['away']['name'] }}" class="team-crest">
                                            </td>
                                            {{-- Conditionally render inputs based on whether the match is bettable --}}
                                            @foreach (['home_win', 'draw', 'away_win'] as $outcome)
                                                <td>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" 
                                                               name="predictions[{{ $index }}][outcome]" 
                                                               id="{{ $outcome }}_dt_{{ $match['id'] }}" 
                                                               value="{{ $outcome }}"
                                                               {{ $existingPrediction?->predicted_outcome == $outcome ? 'checked' : '' }}
                                                               @if($isBettable) required @else disabled @endif>
                                                    </div>
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- MOBILE VIEW: CARDS --}}
                    <div class="d-lg-none">
                        @foreach ($matches as $index => $match)
                            @php
                                $existingPrediction = $existingSlip?->predictions->firstWhere('match_id', $match['id']);
                                $isBettable = $match['is_bettable'] && !$existingSlip?->is_submitted;
                            @endphp
                            <div class="match-card-mobile {{ !$isBettable ? 'match-locked' : '' }}">
                                <div class="match-card-header">
                                    {{ \Carbon\Carbon::parse($match['utcDate'])->format('D, M j, Y - H:i') }} (UTC)
                                    @if (!$isBettable)
                                        <span class="badge badge-danger ml-2">Locked</span>
                                    @endif
                                </div>
                                <div class="match-card-body">
                                    <div class="team-info">
                                        <img src="{{ $match['home']['crest'] ?? asset('images/default_crest.png') }}" alt="{{ $match['home']['name'] }}" class="team-crest-mobile">
                                        <span class="team-name-mobile">{{ $match['home']['name'] }}</span>
                                    </div>
                                    <div class="vs-separator-mobile">VS</div>
                                    <div class="team-info">
                                        <img src="{{ $match['away']['crest'] ?? asset('images/default_crest.png') }}" alt="{{ $match['away']['name'] }}" class="team-crest-mobile">
                                        <span class="team-name-mobile">{{ $match['away']['name'] }}</span>
                                    </div>
                                </div>
                                <div class="match-card-footer">
                                    @if ($isBettable)
                                        <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                            <label class="btn btn-outline-primary {{ $existingPrediction?->predicted_outcome == 'home_win' ? 'active' : '' }}">
                                                <input type="radio" name="predictions[{{ $index }}][outcome]" value="home_win" required> Home Win
                                            </label>
                                            <label class="btn btn-outline-primary {{ $existingPrediction?->predicted_outcome == 'draw' ? 'active' : '' }}">
                                                <input type="radio" name="predictions[{{ $index }}][outcome]" value="draw" required> Draw
                                            </label>
                                            <label class="btn btn-outline-primary {{ $existingPrediction?->predicted_outcome == 'away_win' ? 'active' : '' }}">
                                                <input type="radio" name="predictions[{{ $index }}][outcome]" value="away_win" required> Away Win
                                            </label>
                                        </div>
                                    @else
                                        {{-- Show the user's pick if it exists, otherwise show 'Betting Closed' --}}
                                        @php
                                            $pickText = 'Betting Closed';
                                            if ($existingPrediction) {
                                                $pickText = 'Your Pick: ' . str_replace('_', ' ', Str::title($existingPrediction->predicted_outcome));
                                            }
                                        @endphp
                                        <div class="text-center text-muted font-weight-bold">{{ $pickText }}</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 text-center">
                        <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-check-circle mr-2"></i>Submit Predictions</button>
                    </div>
                </form>

            {{-- Submitted View --}}
            @elseif ($existingSlip && $existingSlip->is_submitted && !empty($matches))
                <div class="submitted-picks">
                    @foreach($matches as $match)
                        @php
                            $prediction = $existingSlip->predictions->firstWhere('match_id', $match['id']);
                            $userPick = 'Not Predicted';
                            $pickClass = 'secondary';
                            if ($prediction) {
                                switch ($prediction->predicted_outcome) {
                                    case 'home_win':
                                        $userPick = $match['home']['name'] . ' Win';
                                        $pickClass = 'primary';
                                        break;
                                    case 'draw':
                                        $userPick = 'Draw';
                                        $pickClass = 'warning';
                                        break;
                                    case 'away_win':
                                        $userPick = $match['away']['name'] . ' Win';
                                        $pickClass = 'info';
                                        break;
                                }
                            }
                        @endphp
                        <div class="submitted-pick-card">
                            <div class="match-info">
                                <img src="{{ $match['home']['crest'] ?? '' }}" class="team-crest-sm" alt="">
                                <span class="team-name-submitted">{{ $match['home']['name'] }} vs {{ $match['away']['name'] }}</span>
                                <img src="{{ $match['away']['crest'] ?? '' }}" class="team-crest-sm" alt="">
                            </div>
                            <div class="pick-info">
                                <span class="text-muted mr-2">Your Pick:</span>
                                <span class="badge badge-pill badge-{{ $pickClass }}">{{ $userPick }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

            {{-- No Matches View --}}
            @elseif (empty($matches) && !$message)
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-2x mb-2"></i>
                    <p>No matches found for the current betting week.</p>
                </div>
            @endif
        </div>
    </div>
@stop

@push('css')
<style>
    /* --- General & Responsive Header --- */
    .responsive-header {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
    }

    /* --- Locked Match Styling --- */
    .match-locked {
        background-color: #f1f1f1 !important;
        opacity: 0.7;
    }
    .match-locked .team-name {
        color: #6c757d;
    }

    /* --- Desktop Table Styles --- */
    .betting-table th, .betting-table td {
        text-align: center;
        vertical-align: middle !important;
    }
    .betting-table .match-cell {
        text-align: left;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
    }
    .betting-table .team-crest {
        width: 25px;
        height: 25px;
    }
    .betting-table .vs-separator {
        color: #888;
        font-weight: normal;
    }
    .betting-table .form-check {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100%;
    }
    .betting-table .form-check-input {
        transform: scale(1.5);
        cursor: pointer;
    }
    .betting-table .form-check-input:disabled {
        cursor: not-allowed;
        opacity: 0.5;
    }
    
    /* --- Mobile Card Styles --- */
    .match-card-mobile {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        margin-bottom: 1rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    .match-card-header {
        background: #e9ecef;
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
        color: #495057;
        text-align: center;
        font-weight: 600;
    }
    .match-card-body {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
    }
    .team-info {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    .team-crest-mobile {
        width: 45px;
        height: 45px;
        margin-bottom: 0.5rem;
    }
    .team-name-mobile {
        font-weight: bold;
        font-size: 1rem;
    }
    .vs-separator-mobile {
        font-size: 1.2rem;
        font-weight: 300;
        color: #6c757d;
        padding: 0 1rem;
    }
    .match-card-footer {
        padding: 0.75rem;
        background: #f8f9fa;
    }
    .match-card-footer .btn-group {
        width: 100%;
    }
    .match-card-footer .btn {
        font-size: 0.85rem;
    }
    .match-card-footer input[type="radio"] {
        position: absolute;
        clip: rect(0,0,0,0);
        pointer-events: none;
    }

    /* --- Submitted Picks Styles --- */
    .submitted-picks {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    .submitted-pick-card {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 1rem;
        background-color: #f8f9fa;
        border: 1px solid #e3e6ea;
        border-radius: .25rem;
    }
    .submitted-pick-card .match-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-grow: 1;
        margin-right: 1rem;
    }
    .submitted-pick-card .team-name-submitted {
        font-weight: 600;
    }
    .submitted-pick-card .team-crest-sm {
        width: 22px;
        height: 22px;
    }
    .submitted-pick-card .pick-info .badge {
        font-size: 0.9em;
        padding: .5em .8em;
    }
</style>
@endpush