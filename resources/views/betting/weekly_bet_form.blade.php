@extends('adminlte::page')

@section('title', 'Weekly Premier League Bets')

@section('content_header')
    <h1><i class="fas fa-futbol mr-2"></i>Premier League - Bet on This Week's Matches</h1>
@stop

@section('content')
    <div class="card card-primary card-outline">
        <div class="card-header responsive-header">
            <h3 class="card-title mb-1 mb-md-0">
                <i class="fas fa-calendar-week mr-2"></i>Week: {{ $weekIdentifier ?? 'N/A' }}
            </h3>
            @if($firstMatchTime)
            <div class="card-tools">
                <span class="badge badge-info mr-1"><i class="far fa-clock mr-1"></i>Closes: {{ $firstMatchTime->setTimezone(config('app.timezone', 'UTC'))->format('D, M j H:i T') }}</span>
                <span class="badge {{ $currentTime->lt($firstMatchTime) ? 'badge-success' : 'badge-danger' }}">
                    {{ $currentTime->lt($firstMatchTime) ? 'Open' : 'Closed' }}
                </span>
            </div>
            @endif
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
            @if ($bettingOpen && !empty($matches))
                <form method="POST" action="{{ route('betting.store') }}">
                    @csrf

                    {{-- DESKTOP VIEW: TABLE --}}
                    <div class="d-none d-lg-block">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover betting-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 15%;">Date (UTC)</th>
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
                                        @endphp
                                        <tr>
                                            <td>
                                                {{ \Carbon\Carbon::parse($match['utcDate'])->format('D, M j H:i') }}
                                                <input type="hidden" name="predictions[{{ $index }}][match_id]" value="{{ $match['id'] }}">
                                            </td>
                                            <td class="match-cell">
                                                <img src="{{ $match['home']['crest'] ?? asset('images/default_crest.png') }}" alt="{{ $match['home']['name'] }}" class="team-crest">
                                                <span class="team-name">{{ $match['home']['name'] }}</span>
                                                <span class="vs-separator">vs</span>
                                                <span class="team-name">{{ $match['away']['name'] }}</span>
                                                <img src="{{ $match['away']['crest'] ?? asset('images/default_crest.png') }}" alt="{{ $match['away']['name'] }}" class="team-crest">
                                            </td>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="predictions[{{ $index }}][outcome]" id="home_win_dt_{{ $match['id'] }}" value="home_win" {{ ($existingPrediction?->predicted_outcome == 'home_win' && !$existingSlip?->is_submitted) ? 'checked' : '' }} required {{ $existingSlip?->is_submitted ? 'disabled' : '' }}>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="predictions[{{ $index }}][outcome]" id="draw_dt_{{ $match['id'] }}" value="draw" {{ ($existingPrediction?->predicted_outcome == 'draw' && !$existingSlip?->is_submitted) ? 'checked' : '' }} required {{ $existingSlip?->is_submitted ? 'disabled' : '' }}>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="predictions[{{ $index }}][outcome]" id="away_win_dt_{{ $match['id'] }}" value="away_win" {{ ($existingPrediction?->predicted_outcome == 'away_win' && !$existingSlip?->is_submitted) ? 'checked' : '' }} required {{ $existingSlip?->is_submitted ? 'disabled' : '' }}>
                                                </div>
                                            </td>
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
                            @endphp
                            <div class="match-card-mobile">
                                <div class="match-card-header">
                                    {{ \Carbon\Carbon::parse($match['utcDate'])->format('D, M j, Y - H:i') }} (UTC)
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
                                    <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                        <label class="btn btn-outline-primary {{ ($existingPrediction?->predicted_outcome == 'home_win' && !$existingSlip?->is_submitted) ? 'active' : '' }}">
                                            <input type="radio" name="predictions[{{ $index }}][outcome]" value="home_win" id="home_win_mb_{{ $match['id'] }}" {{ ($existingPrediction?->predicted_outcome == 'home_win' && !$existingSlip?->is_submitted) ? 'checked' : '' }} required {{ $existingSlip?->is_submitted ? 'disabled' : '' }}> Home Win
                                        </label>
                                        <label class="btn btn-outline-primary {{ ($existingPrediction?->predicted_outcome == 'draw' && !$existingSlip?->is_submitted) ? 'active' : '' }}">
                                            <input type="radio" name="predictions[{{ $index }}][outcome]" value="draw" id="draw_mb_{{ $match['id'] }}" {{ ($existingPrediction?->predicted_outcome == 'draw' && !$existingSlip?->is_submitted) ? 'checked' : '' }} required {{ $existingSlip?->is_submitted ? 'disabled' : '' }}> Draw
                                        </label>
                                        <label class="btn btn-outline-primary {{ ($existingPrediction?->predicted_outcome == 'away_win' && !$existingSlip?->is_submitted) ? 'active' : '' }}">
                                            <input type="radio" name="predictions[{{ $index }}][outcome]" value="away_win" id="away_win_mb_{{ $match['id'] }}" {{ ($existingPrediction?->predicted_outcome == 'away_win' && !$existingSlip?->is_submitted) ? 'checked' : '' }} required {{ $existingSlip?->is_submitted ? 'disabled' : '' }}> Away Win
                                        </label>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if (!$existingSlip || !$existingSlip->is_submitted)
                        <div class="mt-4 text-center">
                            <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-check-circle mr-2"></i>Submit Predictions</button>
                        </div>
                    @endif
                </form>

            {{-- Submitted View --}}
            @elseif ($existingSlip && $existingSlip->is_submitted && !empty($matches))
                <div class="alert alert-info">
                    <h5 class="alert-heading"><i class="fas fa-receipt"></i> Your Bets Are In!</h5>
                    <p>Your predictions for this week have been submitted. Good luck!</p>
                </div>
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
    /* Hide the actual radio button but keep it accessible */
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
        margin-right: 1rem; /* Space between match and pick on wider screens */
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