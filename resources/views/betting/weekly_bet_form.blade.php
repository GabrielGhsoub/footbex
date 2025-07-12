@extends('adminlte::page') {{-- Or your main layout --}}

@section('title', 'Weekly Premier League Bets')

@section('content_header')
    <h1>Premier League - Bet on This Week's Matches</h1>
@stop

@section('content')
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title">Week: {{ $weekIdentifier ?? 'N/A' }}</h3>
            @if($firstMatchTime)
            <div class="card-tools">
                <span class="badge badge-info">Betting Closes: {{ $firstMatchTime->setTimezone(config('app.timezone', 'UTC'))->format('D, M j, Y H:i T') }}</span>
                 <span class="badge {{ $currentTime->lt($firstMatchTime) ? 'badge-success' : 'badge-danger' }}">
                    {{ $currentTime->lt($firstMatchTime) ? 'Open' : 'Closed' }}
                </span>
            </div>
            @endif
        </div>
        <div class="card-body">
            @if (session('status'))
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    {{ session('status') }}
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    {{ session('error') }}
                </div>
            @endif

            @if ($message)
                <div class="alert alert-warning">{{ $message }}</div>
            @endif

            @if ($bettingOpen && !empty($matches))
                <form method="POST" action="{{ route('betting.store') }}">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Date (UTC)</th>
                                    <th>Match</th>
                                    <th>Home Win</th>
                                    <th>Draw</th>
                                    <th>Away Win</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($matches as $index => $match)
                                    @php
                                        $existingPrediction = null;
                                        if ($existingSlip && $existingSlip->predictions) {
                                            foreach ($existingSlip->predictions as $pred) {
                                                if ($pred->match_id == $match['id']) {
                                                    $existingPrediction = $pred->predicted_outcome;
                                                    break;
                                                }
                                            }
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            {{ \Carbon\Carbon::parse($match['utcDate'])->format('D, M j H:i') }}
                                            <input type="hidden" name="predictions[{{ $index }}][match_id]" value="{{ $match['id'] }}">
                                        </td>
                                        <td>
                                            <img src="{{ $match['home']['crest'] ?? asset('images/default_crest.png') }}" alt="{{ $match['home']['name'] }}" style="width: 20px; height: 20px; margin-right: 5px;">
                                            {{ $match['home']['name'] }} vs 
                                            <img src="{{ $match['away']['crest'] ?? asset('images/default_crest.png') }}" alt="{{ $match['away']['name'] }}" style="width: 20px; height: 20px; margin-left: 5px;">
                                            {{ $match['away']['name'] }}
                                        </td>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" 
                                                       name="predictions[{{ $index }}][outcome]" 
                                                       id="home_win_{{ $match['id'] }}" value="home_win"
                                                       {{ ($existingPrediction == 'home_win' && !$existingSlip?->is_submitted) ? 'checked' : '' }}
                                                       required {{ $existingSlip && $existingSlip->is_submitted ? 'disabled' : '' }}>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" 
                                                       name="predictions[{{ $index }}][outcome]" 
                                                       id="draw_{{ $match['id'] }}" value="draw"
                                                       {{ ($existingPrediction == 'draw' && !$existingSlip?->is_submitted) ? 'checked' : '' }}
                                                       required {{ $existingSlip && $existingSlip->is_submitted ? 'disabled' : '' }}>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" 
                                                       name="predictions[{{ $index }}][outcome]" 
                                                       id="away_win_{{ $match['id'] }}" value="away_win"
                                                       {{ ($existingPrediction == 'away_win' && !$existingSlip?->is_submitted) ? 'checked' : '' }}
                                                       required {{ $existingSlip && $existingSlip->is_submitted ? 'disabled' : '' }}>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if (!$existingSlip || !$existingSlip->is_submitted)
                    <div class="mt-3 text-center">
                        <button type="submit" class="btn btn-primary btn-lg">Submit Predictions</button>
                    </div>
                    @endif
                </form>
            @elseif ($existingSlip && $existingSlip->is_submitted && !empty($matches))
                 <p class="text-info">Your predictions for this week:</p>
                 <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Match</th><th>Your Pick</th></tr></thead>
                        <tbody>
                        @foreach($matches as $match)
                            @php
                                $userPick = 'Not Predicted';
                                foreach($existingSlip->predictions as $p) {
                                    if($p->match_id == $match['id']) {
                                        $userPick = ucfirst(str_replace('_', ' ', $p->predicted_outcome));
                                        break;
                                    }
                                }
                            @endphp
                            <tr>
                                <td>{{ $match['home']['name'] }} vs {{ $match['away']['name'] }}</td>
                                <td>{{ $userPick }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                 </div>
            @elseif (empty($matches) && !$message)
                <div class="alert alert-info">No matches found for the current betting week.</div>
            @endif
        </div>
    </div>
@stop

@push('css')
<style>
    .form-check { display: flex; justify-content: center; align-items: center; height: 100%; }
    .form-check-input { transform: scale(1.5); }
    th, td { text-align: center; vertical-align: middle !important; }
    td:nth-child(2) { text-align: left; } /* Match name align left */
</style>
@endpush