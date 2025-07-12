{{-- resources/views/match_details.blade.php --}}

@extends('adminlte::page')

@section('title', $match['homeTeam']['name'] . ' vs ' . $match['awayTeam']['name'] . ' - Match Details')

@section('content_header')
    {{-- Use a fluid container for better spacing and control --}}
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col-md-8">
                {{-- Main Title --}}
                <h1 class="m-0 text-dark font-weight-bold">
                    <img src="{{ $match['homeTeam']['crest'] ?? asset('images/default_crest.png') }}" alt="{{ $match['homeTeam']['name'] }}" class="team-crest-header mr-2" onerror="this.style.display='none'; this.nextSibling.style.display='inline-block';">
                    <span class="d-none team-crest-placeholder-header mr-2"></span>{{ $match['homeTeam']['name'] ?? 'Home Team' }}
                    <span class="mx-3 text-muted">vs</span>
                    {{ $match['awayTeam']['name'] ?? 'Away Team' }}<img src="{{ $match['awayTeam']['crest'] ?? asset('images/default_crest.png') }}" alt="{{ $match['awayTeam']['name'] }}" class="team-crest-header ml-2" onerror="this.style.display='none'; this.previousSibling.style.display='inline-block';">
                     <span class="d-none team-crest-placeholder-header ml-2"></span>
                </h1>
                <p class="text-muted mb-0">{{ $match['competition']['name'] ?? 'Competition' }} - {{ \Carbon\Carbon::parse($match['utcDate'])->setTimezone('Europe/London')->format('D, M j, Y') }}</p> {{-- Adjust timezone as needed --}}
            </div>
            <div class="col-md-4">
                {{-- Breadcrumbs --}}
                <ol class="breadcrumb float-md-right">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ url('/matches') }}">Matches</a></li> {{-- Assuming '/matches' is your fixtures list route --}}
                    <li class="breadcrumb-item active">Details</li>
                </ol>
            </div>
        </div>
    </div>
@stop

@section('content')
<div class="container-fluid">
    <div class="row">
        {{-- Match Overview Card --}}
        <div class="col-12">
            <div class="card match-details-card shadow-lg animated fadeIn">
                <div class="card-body p-0"> {{-- Remove padding to let inner divs control it --}}
                    <div class="match-header p-4">
                        {{-- Teams and Score Row --}}
                        <div class="row align-items-center text-center">
                            {{-- Home Team --}}
                            <div class="col-4 col-md-5 team-section">
                                <img src="{{ $match['homeTeam']['crest'] ?? asset('images/default_crest.png') }}" alt="{{ $match['homeTeam']['name'] }}" class="team-crest-main mb-2" onerror="this.style.display='none'; this.nextSibling.style.display='inline-block';">
                                 <span class="d-none team-crest-placeholder-main mb-2"></span>
                                <h2 class="team-name h5 mb-0 font-weight-bold">{{ $match['homeTeam']['name'] ?? 'Home' }}</h2>
                                <span class="team-tla text-muted d-block">{{ $match['homeTeam']['tla'] ?? '' }}</span>
                            </div>

                            {{-- Score / Status / Time --}}
                            <div class="col-4 col-md-2 score-section">
                                @if ($match['status'] == 'FINISHED' || $match['status'] == 'IN_PLAY' || $match['status'] == 'PAUSED')
                                    <div class="score-display mb-1">
                                        {{ $match['score']['fullTime']['home'] ?? '?' }} - {{ $match['score']['fullTime']['away'] ?? '?' }}
                                    </div>
                                    @if ($match['status'] == 'FINISHED')
                                        <span class="status-tag finished small">Finished</span>
                                        @if (isset($match['score']['halfTime']['home']))
                                        <div class="half-time-score text-muted small mt-1">
                                            (HT: {{ $match['score']['halfTime']['home'] }} - {{ $match['score']['halfTime']['away'] }})
                                        </div>
                                        @endif
                                    @elseif ($match['status'] == 'IN_PLAY')
                                        <span class="status-tag in_play small live">Live</span>
                                        {{-- You might add live minute here if API provides it --}}
                                    @elseif ($match['status'] == 'PAUSED')
                                        <span class="status-tag paused small">Half Time</span>
                                    @endif
                                @else
                                     <div class="time-display mb-1">
                                        {{ \Carbon\Carbon::parse($match['utcDate'])->setTimezone('Europe/London')->format('H:i') }} {{-- Adjust timezone as needed --}}
                                     </div>
                                     @php
                                        $statusClass = strtolower($match['status'] ?? 'scheduled');
                                        $statusText = ucwords(str_replace('_', ' ', $match['status'] ?? 'Scheduled'));
                                     @endphp
                                     <span class="status-tag {{ $statusClass }} small">{{ $statusText }}</span>
                                @endif
                            </div>

                            {{-- Away Team --}}
                            <div class="col-4 col-md-5 team-section">
                                 <img src="{{ $match['awayTeam']['crest'] ?? asset('images/default_crest.png') }}" alt="{{ $match['awayTeam']['name'] }}" class="team-crest-main mb-2" onerror="this.style.display='none'; this.nextSibling.style.display='inline-block';">
                                 <span class="d-none team-crest-placeholder-main mb-2"></span>
                                <h2 class="team-name h5 mb-0 font-weight-bold">{{ $match['awayTeam']['name'] ?? 'Away' }}</h2>
                                <span class="team-tla text-muted d-block">{{ $match['awayTeam']['tla'] ?? '' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Additional Details Section --}}
                    <div class="match-info p-4 border-top">
                        <div class="row">
                             <div class="col-md-6">
                                <h5 class="section-title mb-3">Match Information</h5>
                                <ul class="list-unstyled info-list">
                                    <li>
                                        <i class="fas fa-calendar-alt fa-fw mr-2 text-muted"></i>
                                        <strong>Date:</strong> {{ \Carbon\Carbon::parse($match['utcDate'])->setTimezone('Europe/London')->format('l, F jS, Y') }} {{-- Adjust timezone --}}
                                    </li>
                                    <li>
                                        <i class="fas fa-clock fa-fw mr-2 text-muted"></i>
                                        <strong>Kick-off:</strong> {{ \Carbon\Carbon::parse($match['utcDate'])->setTimezone('Europe/London')->format('H:i T') }} {{-- Adjust timezone --}}
                                    </li>
                                     <li>
                                        <i class="fas fa-trophy fa-fw mr-2 text-muted"></i>
                                        <strong>Competition:</strong> {{ $match['competition']['name'] ?? 'N/A' }} ({{ $match['competition']['code'] ?? 'N/A' }})
                                    </li>
                                     <li>
                                        <i class="fas fa-tasks fa-fw mr-2 text-muted"></i>
                                        <strong>Matchday:</strong> {{ $match['matchday'] ?? 'N/A' }}
                                    </li>
                                     <li>
                                        <i class="fas fa-flag fa-fw mr-2 text-muted"></i>
                                        <strong>Area:</strong> {{ $match['area']['name'] ?? 'N/A' }}
                                        @if(isset($match['area']['flag']) && $match['area']['flag'])
                                            <img src="{{ $match['area']['flag'] }}" alt="{{ $match['area']['name'] }} flag" style="height: 1em; vertical-align: middle; margin-left: 5px;">
                                        @endif
                                    </li>
                                    <li>
                                        <i class="fas fa-map-marker-alt fa-fw mr-2 text-muted"></i>
                                        <strong>Venue:</strong> {{ $match['venue'] ?? 'N/A' }}
                                    </li>
                                    <li>
                                        <i class="fas fa-sync fa-fw mr-2 text-muted"></i>
                                        <strong>Last Updated:</strong> {{ isset($match['lastUpdated']) ? \Carbon\Carbon::parse($match['lastUpdated'])->diffForHumans() : 'N/A' }}
                                    </li>
                                </ul>
                             </div>
                              <div class="col-md-6">
                                 <h5 class="section-title mb-3">Officials</h5>
                                @if (!empty($match['referees']))
                                    <ul class="list-unstyled info-list">
                                        @foreach($match['referees'] as $ref)
                                            <li>
                                                <i class="fas {{ $ref['type'] == 'REFEREE' ? 'fa-whistle' : 'fa-user-tie' }} fa-fw mr-2 text-muted"></i>
                                                <strong>{{ ucwords(strtolower(str_replace('_', ' ', $ref['type'] ?? 'Official'))) }}:</strong>
                                                {{ $ref['name'] ?? 'N/A' }}
                                                @if(isset($ref['nationality']) && $ref['nationality'])
                                                    <span class="text-muted">({{ $ref['nationality'] }})</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-muted">Referee information not available.</p>
                                @endif

                                {{-- Placeholder for other details like odds if available --}}
                                @if(isset($match['odds']) && !isset($match['odds']['msg']))
                                    <h5 class="section-title mt-4 mb-3">Odds</h5>
                                    {{-- Display Odds Here --}}
                                    <p class="text-muted">Odds display logic goes here.</p>
                                @elseif(isset($match['odds']['msg']))
                                    {{-- <p class="text-muted mt-3 small">{{ $match['odds']['msg'] }}</p> --}}
                                @endif
                             </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- You could add more cards here for Head-to-Head, Standings, etc. if you fetch that data --}}

    </div>
</div>
@stop

@push('css')
<style>
    /* --- Animation --- */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .animated {
        animation-duration: 0.6s;
        animation-fill-mode: both;
    }

    .fadeIn {
        animation-name: fadeIn;
    }

    /* --- Card Styling --- */
    .match-details-card {
        border: none; /* Remove default border if shadow is enough */
        border-radius: 0.6rem;
        overflow: hidden; /* Ensure content respects border radius */
        background-color: #ffffff;
    }

    .match-header {
        background: linear-gradient(to bottom, #f8f9fa, #ffffff); /* Subtle gradient */
        border-bottom: 1px solid #e9ecef;
    }

    .match-info {
        background-color: #fdfdfd;
    }

    /* --- Header Crests --- */
     .team-crest-header, .team-crest-placeholder-header {
        height: 1.8em; /* Adjust size as needed */
        width: 1.8em;
        vertical-align: middle;
        object-fit: contain;
    }
    .team-crest-placeholder-header {
        display: inline-block;
        background-color: #eee;
        border-radius: 50%;
    }

    /* --- Team Section --- */
    .team-section {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 10px 5px;
    }
    .team-crest-main, .team-crest-placeholder-main {
        max-height: 70px; /* Control max crest size */
        max-width: 70px;
        height: auto;
        width: auto;
        object-fit: contain;
    }
     .team-crest-placeholder-main {
        display: inline-block;
        background-color: #eee;
        border-radius: 50%;
        width: 60px; /* Placeholder fixed size */
        height: 60px;
    }
    .team-name {
        color: #333;
        line-height: 1.2;
    }
    .team-tla {
        font-size: 0.85em;
    }

    /* --- Score/Status Section --- */
    .score-section {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
    .score-display {
        font-size: 2.8rem;
        font-weight: 700;
        color: #212529;
        line-height: 1;
    }
    .time-display {
        font-size: 1.8rem;
        font-weight: 600;
        color: #495057;
        line-height: 1;
    }
    .half-time-score {
        font-size: 0.9rem;
    }

    /* --- Status Tags (Copied & adapted from your fixtures CSS) --- */
    .status-tag {
        padding: .25rem .6rem;
        border-radius: 0.25rem;
        font-size: .75rem; /* Slightly larger for details page */
        font-weight: 600;
        color: #fff;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        line-height: 1.2;
        display: inline-block;
        text-align: center;
        margin-top: 5px;
    }
    /* Status colors */
    .status-tag.scheduled, .status-tag.timed { background-color: #17a2b8; } /* Info */
    .status-tag.finished { background-color: #28a745; } /* Success */
    .status-tag.in_play { background-color: #dc3545; } /* Danger */
    .status-tag.paused { background-color: #ffc107; color: #333; } /* Warning */
    .status-tag.postponed { background-color: #6c757d; } /* Secondary */
    .status-tag.suspended { background-color: #fd7e14; } /* Orange */
    .status-tag.canceled { background-color: #343a40; } /* Dark */
    .status-tag.awarded { background-color: #007bff; } /* Primary */
    .status-tag.unknown { background-color: #6c757d; } /* Default/Unknown */

    /* Live Indicator animation */
     @keyframes pulse_live {
        0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
        70% { box-shadow: 0 0 0 8px rgba(220, 53, 69, 0); }
        100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
    }
    .status-tag.live::before {
        content: '';
        display: inline-block;
        width: 8px;
        height: 8px;
        background-color: #fff; /* White dot on red background */
        border-radius: 50%;
        margin-right: 6px;
        animation: pulse_live 1.5s infinite;
    }

    /* --- Info List --- */
    .section-title {
        font-weight: 600;
        color: #007bff; /* Or your theme's primary color */
        padding-bottom: 5px;
        border-bottom: 2px solid #007bff;
        display: inline-block;
    }

    .info-list li {
        margin-bottom: 0.8rem;
        font-size: 0.95rem;
        color: #495057;
    }
    .info-list li i {
        width: 20px; /* Fixed width for icons */
        text-align: center;
        color: #6c757d;
    }
    .info-list strong {
        color: #343a40;
    }

    /* --- Responsive Adjustments --- */
    @media (max-width: 767.98px) {
        .score-display { font-size: 2.2rem; }
        .time-display { font-size: 1.5rem; }
        .team-crest-main, .team-crest-placeholder-main { max-height: 50px; max-width: 50px; }
        .team-crest-placeholder-main { width: 45px; height: 45px; }
        .team-name { font-size: 1rem; }
        .section-title { margin-top: 1.5rem; } /* Add space between columns on mobile */
    }
     @media (max-width: 575.98px) {
         h1.m-0 { font-size: 1.5rem; } /* Adjust header size on mobile */
         .team-crest-header, .team-crest-placeholder-header { height: 1.4em; width: 1.4em; }
         .breadcrumb { padding: 0.5rem 0; font-size: 0.8rem; }
        .score-display { font-size: 1.8rem; }
        .time-display { font-size: 1.3rem; }
        .team-crest-main, .team-crest-placeholder-main { max-height: 40px; max-width: 40px; }
        .team-crest-placeholder-main { width: 35px; height: 35px; }
        .team-name { font-size: 0.9rem; }
        .info-list li { font-size: 0.9rem; }
     }
</style>
@endpush

@push('js')
{{-- Add JS here if needed for dynamic updates later --}}
<script>
    // Example: Add fallback for broken crest images (alternative to onerror)
    // document.addEventListener('error', function (event) {
    //     if (event.target.tagName === 'IMG' && event.target.classList.contains('team-crest')) {
    //         console.warn('Crest image failed to load:', event.target.src);
    //         // Optionally replace with a placeholder
    //         // event.target.src = '/path/to/default_crest.png';
    //         event.target.style.display = 'none'; // Hide broken image icon
    //         // Find a corresponding placeholder if you added one
    //         const placeholder = event.target.nextElementSibling;
    //         if (placeholder && placeholder.classList.contains('team-crest-placeholder')) {
    //              placeholder.style.display = 'inline-block';
    //         }
    //     }
    // }, true); // Use capture phase
</script>
@endpush