@extends('adminlte::page')

@section('title', 'Set Double Point Match')

@section('content_header')
    <h1><i class="fas fa-bolt mr-2"></i>Set Weekly Double Point Match</h1>
@stop

@section('content')
<div class="card shadow-sm">
    <div class="card-body">
        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="icon fas fa-check"></i>{{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="icon fas fa-exclamation-triangle"></i>{{ session('error') }}
            </div>
        @endif

        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-2"></i>
            <strong>Instructions:</strong> Select ONE match from the upcoming week to be the double points opportunity. All users will see this match highlighted and can opt-in to participate. You must approve each user's opt-in request.
        </div>

        @if($existingMatch)
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <strong>Current Selection for Week {{ $weekIdentifier }}:</strong><br>
                {{ $existingMatch->home_team_name }} vs {{ $existingMatch->away_team_name }}
                <br>
                <small>Set by {{ $existingMatch->admin->name }} on {{ $existingMatch->created_at->format('M j, Y H:i') }}</small>
            </div>
        @endif

        <form id="setMatchForm" method="POST" action="{{ route('admin.double_point.store_match') }}">
            @csrf
            <input type="hidden" name="week_identifier" value="{{ $weekIdentifier }}">
            <input type="hidden" name="match_id" id="match_id">
            <input type="hidden" name="home_team_name" id="home_team_name">
            <input type="hidden" name="away_team_name" id="away_team_name">

            <div class="form-group">
                <label><i class="fas fa-calendar-week mr-1"></i>Week Identifier</label>
                <input type="text" class="form-control" value="{{ $weekIdentifier }}" readonly>
                <small class="form-text text-muted">This is the ISO week format (YYYY-WW)</small>
            </div>

            <div class="form-group">
                <label><i class="fas fa-futbol mr-1"></i>Select Match <span class="text-danger">*</span></label>
                <p class="text-muted">Click on a match below to select it as the double points opportunity:</p>
            </div>

            <div id="matchesContainer" class="mb-3">
                @if($message)
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p>{{ $message }}</p>
                    </div>
                @elseif(empty($matches))
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p>No matches available for this week.</p>
                    </div>
                @else
                    @foreach($matches as $match)
                        <div class="card match-card mb-2" style="cursor: pointer;" onclick="selectMatch({{ $match['id'] }}, '{{ addslashes($match['home']['name']) }}', '{{ addslashes($match['away']['name']) }}')">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="mb-0">
                                            @if(isset($match['home']['crest']))
                                                <img src="{{ $match['home']['crest'] }}" alt="" style="width: 25px; height: 25px;" class="mr-2">
                                            @endif
                                            {{ $match['home']['name'] }}
                                            <span class="text-muted mx-2">vs</span>
                                            {{ $match['away']['name'] }}
                                            @if(isset($match['away']['crest']))
                                                <img src="{{ $match['away']['crest'] }}" alt="" style="width: 25px; height: 25px;" class="ml-2">
                                            @endif
                                        </h5>
                                    </div>
                                    <div class="col-md-4 text-right">
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($match['utcDate'])->format('M j, Y H:i') }} UTC</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            <div id="selectedMatchDisplay" class="alert alert-success d-none">
                <i class="fas fa-check-circle mr-2"></i>
                <strong>Selected Match:</strong> <span id="selectedMatchText"></span>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" disabled>
                <i class="fas fa-bolt mr-2"></i>Set as Double Point Match
            </button>
        </form>
    </div>
</div>
@stop

@push('js')
<script>
function selectMatch(matchId, homeTeam, awayTeam) {
    // Update hidden fields
    document.getElementById('match_id').value = matchId;
    document.getElementById('home_team_name').value = homeTeam;
    document.getElementById('away_team_name').value = awayTeam;

    // Show selected match
    document.getElementById('selectedMatchText').textContent = `${homeTeam} vs ${awayTeam}`;
    document.getElementById('selectedMatchDisplay').classList.remove('d-none');

    // Enable submit button
    document.getElementById('submitBtn').disabled = false;

    // Highlight selected card
    document.querySelectorAll('.match-card').forEach(card => {
        card.classList.remove('border-success');
        card.style.backgroundColor = '';
    });
    event.currentTarget.classList.add('border-success');
    event.currentTarget.style.backgroundColor = '#d4edda';
}
</script>
@endpush

@push('css')
<style>
    .match-card {
        transition: all 0.3s ease;
    }
    .match-card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
</style>
@endpush
