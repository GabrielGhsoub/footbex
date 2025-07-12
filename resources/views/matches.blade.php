{{-- resources/views/matches.blade.php --}}
@extends('adminlte::page')

@section('title', 'This Week’s Premier League Matches')

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Premier League Fixtures</h1> {{-- Use m-0 for tighter heading --}}
            </div>
            <div class="col-sm-6">
                 <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="/">Home</a></li> {{-- Link to dashboard or home route --}}
                    <li class="breadcrumb-item active">Weekly Fixtures</li>
                 </ol>
            </div>
        </div>
    </div>
@stop

@section('content')
<div class="container-fluid">
    {{-- Info Boxes --}}
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12 mb-3">
            <div class="info-box shadow-sm">
                <span class="info-box-icon bg-info elevation-1"><i class="far fa-calendar-alt"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Matches This Week</span>
                    <span class="info-box-number" id="matchesThisWeek">0</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12 mb-3">
            <div class="info-box shadow-sm">
                <span class="info-box-icon bg-success elevation-1"><i class="fas fa-futbol"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Goals</span>
                    <span class="info-box-number" id="totalGoals">0</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12 mb-3">
             <div class="info-box shadow-sm">
                <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-percentage"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Avg Goals/Match</span>
                    <span class="info-box-number" id="avgGoals">0.00</span>
                </div>
            </div>
        </div>
         <div class="col-lg-3 col-md-6 col-sm-6 col-12 mb-3">
            <div class="info-box shadow-sm">
                <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-clock"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Upcoming</span>
                    <span class="info-box-number" id="upcomingMatches">0</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Loading Overlay --}}
     <div id="loadingOverlay" class="overlay-dark" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); align-items: center; justify-content: center; z-index: 1050; color: white;">
        <i class="fas fa-3x fa-sync-alt fa-spin"></i>
        <span class="sr-only">Loading...</span>
    </div>

    {{-- Fixtures Container --}}
    <div id="fixturesContainer">
        {{-- Ajax loaded content --}}
    </div>

    {{-- Template for No Fixtures message --}}
    <template id="noFixturesTemplate">
         <div class="alert alert-light text-center border mt-4" role="alert">
            No fixtures scheduled for this week or unable to load data.
        </div>
    </template>
</div>
@stop

@push('js')
<script>
document.addEventListener('DOMContentLoaded', fetchAndRender);

function fetchAndRender() {
    const overlay   = document.getElementById('loadingOverlay');
    const container = document.getElementById('fixturesContainer');
    if (!overlay || !container) {
        console.error("Required elements (loadingOverlay or fixturesContainer) not found.");
        return;
    }
    overlay.style.display = 'flex';
    container.innerHTML = '';

    // Ensure the route name 'home' correctly points to your HomeController@index method
    const apiUrl = "{{ route('home') }}";

    fetch(apiUrl)
        .then(response => {
            if (!response.ok) {
                 // Try to get error message from JSON response if possible
                 return response.json().then(errData => {
                     throw new Error(`HTTP error ${response.status}: ${errData.message || response.statusText}`);
                 }).catch(() => {
                     // Fallback if response is not JSON
                     throw new Error(`HTTP error! status: ${response.status} ${response.statusText}`);
                 });
            }
            return response.json();
        })
        .then(res => {
            overlay.style.display = 'none';

            // *** ADDED LOGGING HERE ***
            console.log('API Response Data:', res);

            if (!res.success || !res.data || !Array.isArray(res.data)) {
                console.warn('API reported failure or data is invalid:', res.message || 'Invalid data format');
                showEmpty(res.message || 'No match data available.'); // Show API message if available
                updateInfoBoxes([]);
                return;
            }

             if (res.data.length === 0) {
                 console.log('No matches found for the current week.');
                 showEmpty('No fixtures scheduled for this week.');
                 updateInfoBoxes([]);
                 return;
             }

            console.log(`Received ${res.data.length} matches from backend.`);
            const byDate = groupBy(res.data, item => item.date);
            console.log('Grouped by date keys:', Object.keys(byDate));

            updateInfoBoxes(res.data);
            renderFixtures(byDate);
        })
        .catch(err => {
            console.error('Fetch error:', err);
            overlay.style.display = 'none';
            // Display a user-friendly error, potentially extracted from the caught error
            showEmpty(`Error loading fixtures: ${err.message || 'Please try again later.'}`);
            updateInfoBoxes([]);
        });
}

function groupBy(arr, keyFn) {
    if (!Array.isArray(arr)) return {}; // Handle cases where arr is not an array
    return arr.reduce((acc, item) => {
        if (!item) return acc; // Skip null/undefined items
        const key = typeof keyFn === 'function' ? keyFn(item) : item[keyFn];
        if (key === undefined || key === null || key === '') { // Also check for empty string keys
             console.warn('Skipping item due to invalid group key:', key, item);
             return acc;
        }
        if (!acc[key]) acc[key] = [];
        acc[key].push(item);
        return acc;
    }, {});
}

function updateInfoBoxes(matches) {
    // Ensure elements exist before updating
    const elMatches = document.getElementById('matchesThisWeek');
    const elGoals = document.getElementById('totalGoals');
    const elAvg = document.getElementById('avgGoals');
    const elUpcoming = document.getElementById('upcomingMatches');

    if (!elMatches || !elGoals || !elAvg || !elUpcoming) {
        console.warn("One or more info box elements not found.");
        return;
    }

    const totalMatches = matches.length;
    let totalGoals = 0;
    let finishedMatchesCount = 0;
    let upcomingMatchesCount = 0;

    matches.forEach(m => {
        // Use optional chaining for safer access
        if (m?.status?.finished) {
            totalGoals += (parseInt(m.home?.score) || 0) + (parseInt(m.away?.score) || 0);
            finishedMatchesCount++;
        } else if (m?.status?.value && !['FINISHED', 'CANCELLED'].includes(m.status.value)) {
             // Count as upcoming if not finished or cancelled
             upcomingMatchesCount++;
        }
    });

    const avgGoals = finishedMatchesCount > 0 ? (totalGoals / finishedMatchesCount).toFixed(2) : '0.00';

    elMatches.textContent = totalMatches;
    elGoals.textContent = totalGoals;
    elAvg.textContent = avgGoals;
    elUpcoming.textContent = upcomingMatchesCount;
}


function renderFixtures(grouped) {
    const container = document.getElementById('fixturesContainer');
    container.innerHTML = ''; // Clear previous content
    let globalMatchIndex = 0; // For staggered animation delay

    const sortedDateKeys = Object.keys(grouped).sort();

    if (sortedDateKeys.length === 0) {
        showEmpty('No fixtures to display.'); // Use showEmpty function
        return;
    }

    sortedDateKeys.forEach(dateKey => {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(dateKey)) {
             console.warn(`Invalid date key format found: "${dateKey}". Skipping group.`);
             return;
        }

        const dt = new Date(dateKey + 'T00:00:00Z'); // Parse as UTC midnight
        if (isNaN(dt.getTime())) {
              console.warn(`Failed to parse date key: "${dateKey}". Skipping group.`);
             return;
        }

        // Date header
        const header = document.createElement('h5');
        header.className = 'fixture-date mt-4 mb-3 font-weight-bold text-secondary'; // Style header
        header.textContent = dt.toLocaleDateString(undefined, { // Use user's locale formatting
             weekday: 'long', month: 'long', day: 'numeric', timeZone: 'UTC' // Specify UTC TZ
        });
        container.appendChild(header);

        // Matches row
        const row = document.createElement('div');
        row.className = 'row fixture-row'; // Added fixture-row class

        grouped[dateKey]
            .sort((a, b) => (a.time || '00:00').localeCompare(b.time || '00:00')) // Sort by time
            .forEach(m => {
                // *** ADDED LOGGING HERE ***
                console.log('Processing Match Data:', m);
                console.log('Home Crest URL:', m.home?.crest);
                console.log('Away Crest URL:', m.away?.crest);

                const col = document.createElement('div');
                col.className = 'col-lg-4 col-md-6 col-12 mb-3 d-flex fixture-col'; // Responsive columns

                const delay = (globalMatchIndex * 0.05).toFixed(2);
                globalMatchIndex++;

                // Determine status, text, and if live
                let statusText = m?.status?.value?.replace(/_/g, ' ') || 'Unknown';
                statusText = statusText.charAt(0).toUpperCase() + statusText.slice(1).toLowerCase(); // Capitalize first letter
                let statusClass = (m?.status?.value || 'unknown').toLowerCase();
                let isLive = ['in_play', 'paused'].includes(statusClass);

                 // Score/Time Display Logic
                 let scoreOrTimeHtml = `<span class="time">${m.time || 'N/A'}</span>`; // Default: Time
                 if (m?.status?.scoreStr) { // If scoreStr is available (finished or live)
                      scoreOrTimeHtml = `<span class="score-display ${isLive ? 'live' : ''}">${m.status.scoreStr}</span>`;
                 } else if (!m?.status?.started && m.time) {
                      scoreOrTimeHtml = `<span class="time">${m.time}</span>`;
                 } else {
                      scoreOrTimeHtml = `<span class="time">-</span>`; // Fallback if no time/score
                 }

                // Render the card HTML
                col.innerHTML = `
                    <div class="card fixture-card flex-fill h-100 shadow-sm" style="animation-delay: ${delay}s;" ${m.id ? `onclick="viewMatchDetails(${m.id})"` : ''}>
                        <div class="card-body">
                            <div class="match-teams">
                                 {{-- Home Team --}}
                                <div class="team home-team">
                                    <div class="team-info">
                                        ${m.home?.crest ? `<img src="${m.home.crest}" alt="" class="team-crest" onerror="this.style.display='none'">` : '<span class="team-crest-placeholder"></span>'}
                                        <span class="team-name">${m.home?.name || 'Home'}</span>
                                    </div>
                                </div>

                                 {{-- Center Section (Score/Time) --}}
                                <div class="vs-time">
                                    ${scoreOrTimeHtml}
                                </div>

                                 {{-- Away Team --}}
                                <div class="team away-team">
                                     <div class="team-info">
                                        <span class="team-name">${m.away?.name || 'Away'}</span>
                                         ${m.away?.crest ? `<img src="${m.away.crest}" alt="" class="team-crest" onerror="this.style.display='none'">` : '<span class="team-crest-placeholder"></span>'}
                                     </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                             ${isLive ? '<span class="live-indicator" title="Live Match"></span>' : ''} {{-- Add live indicator dot --}}
                            <span class="status-tag ${statusClass}" title="Match Status: ${statusText}">
                                ${statusText}
                            </span>
                        </div>
                    </div>`;
                row.appendChild(col);
            });

        container.appendChild(row);
    });
}


function showEmpty(message = null) {
    const container = document.getElementById('fixturesContainer');
    if (!container) return; // Exit if container doesn't exist
    const template = document.getElementById('noFixturesTemplate');
    container.innerHTML = ''; // Clear previous content

    if (template) {
        const clone = template.content.cloneNode(true);
        const alertDiv = clone.querySelector('.alert');
        if (alertDiv) {
             alertDiv.textContent = message || 'No fixtures available.'; // Use provided message or default
        }
        container.appendChild(clone);
    } else {
        // Fallback if template is somehow missing
        container.innerHTML = `<p class="text-center text-muted mt-4">${message || 'No fixtures available.'}</p>`;
    }
}

function viewMatchDetails(id) {
    if (id) {
        // Make sure the base URL is correct for your routing setup
        window.location.href = `/match-details/${id}`;
    }
}

</script>
@endpush

@push('css')
<style>
    /* General Styles */
    body { background-color: #f4f6f9; }

    /* Date Headers */
    .fixture-date {
        font-size: 1.1rem;
        color: #495057;
        padding-bottom: 0.3rem;
        border-bottom: 1px solid #dee2e6;
        font-weight: 600; /* Make date slightly bolder */
    }

    /* --- Animation Definitions --- */
    @keyframes fadeInSlideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); } /* red */
        70% { box-shadow: 0 0 0 8px rgba(220, 53, 69, 0); }
        100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
    }

    /* Fixture Card Styling */
    .fixture-card {
        border: none;
        border-radius: 0.4rem;
        transition: transform .25s ease-out, box-shadow .25s ease-out, background-color .2s ease-in-out;
        display: flex;
        flex-direction: column;
        background-color: #fff;
        overflow: hidden;
        opacity: 0; /* Start hidden for animation */
        animation: fadeInSlideUp 0.6s cubic-bezier(0.25, 0.8, 0.25, 1) forwards;
        position: relative; /* Needed for potential future absolute elements */
    }
    .fixture-card:hover {
        transform: translateY(-5px) scale(1.01);
        box-shadow: 0 0.6rem 1.2rem rgba(0,0,0,.17) !important;
        background-color: #fdfdfd;
    }
    .fixture-card[onclick] { cursor: pointer; }

    .fixture-card .card-body {
        padding: 0.9rem 1rem;
        flex-grow: 1;
        display: flex;
        align-items: center;
    }

    /* Match Teams Container */
    .match-teams {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
    }

    /* Team Styling (Home & Away) */
    .team {
        flex: 1 1 38%; /* Adjust flex-basis */
        display: flex;
        flex-direction: column;
        align-items: center;
        min-width: 0;
        padding: 0 0.2rem;
    }
    .home-team { align-items: flex-start; text-align: left; }
    .away-team { align-items: flex-end; text-align: right; }

    /* Team Logo and Name Styling */
    .team-info {
        display: flex;
        align-items: center;
        width: 100%;
        gap: 0.5rem; /* Space between crest and name */
    }
    .home-team .team-info { justify-content: flex-start; }
    .away-team .team-info { justify-content: flex-end; }
    /* Visual order for away team using flex order */
    .away-team .team-info .team-name { order: 1; }
    .away-team .team-info .team-crest,
    .away-team .team-info .team-crest-placeholder { order: 2; }

    .team-crest {
        width: 28px;
        height: 28px;
        object-fit: contain;
        flex-shrink: 0;
        vertical-align: middle;
        /* background: #f0f0f0; */ /* Optional subtle bg if crests are transparent */
        /* border-radius: 50%; */ /* Optional: keep logos square or make circular */
    }
    .team-crest-placeholder {
        display: inline-block;
        width: 28px;
        height: 28px;
        flex-shrink: 0;
        /* background-color: #eee; */ /* Optional placeholder color */
        /* border-radius: 50%; */
    }

    .team-name {
        font-size: 0.9rem;
        font-weight: 500;
        color: #333;
        display: block;
        flex-grow: 1;
        width: auto;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.3;
    }

    /* Center element (VS / Time / Score) */
    .vs-time {
        flex: 0 1 auto; /* Allow shrinking */
        padding: 0 0.8rem;
        font-size: 0.85rem;
        color: #555;
        font-weight: 600;
        line-height: 1.2;
        text-align: center;
    }
    .time {
        font-size: 1rem;
        color: #6c757d;
        display: block;
    }
    .score-display {
        font-size: 1.3rem;
        font-weight: 700;
        color: #000;
        letter-spacing: 1px;
        display: block;
        white-space: nowrap;
    }
     .score-display.live {
        color: #dc3545; /* Red for live scores */
        /* font-weight: 800; */ /* Optional: make live score bolder */
     }

    /* Card Footer & Status Tag */
    .fixture-card .card-footer {
        background-color: #f8f9fa;
        padding: 0.5rem 0.8rem;
        text-align: center;
        border-top: 1px solid #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    .live-indicator {
        display: inline-block;
        width: 8px;
        height: 8px;
        background-color: #dc3545;
        border-radius: 50%;
        animation: pulse 1.5s infinite;
        flex-shrink: 0;
    }
    .status-tag {
        padding: .2rem .5rem;
        border-radius: 0.2rem;
        font-size: .7rem;
        font-weight: 600;
        color: #fff;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        line-height: 1.2;
        display: inline-block;
        text-align: center;
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

    /* --- MOBILE RESPONSIVENESS --- */
    @media (max-width: 991.98px) {
        .team-crest, .team-crest-placeholder { width: 26px; height: 26px; }
        .team-name { font-size: 0.85rem; }
        .score-display { font-size: 1.2rem; }
        .time { font-size: 0.9rem; }
    }

    @media (max-width: 767.98px) {
         .fixture-card .card-body { padding: 0.8rem 0.9rem; }
          .team { flex-basis: 35%; } /* Adjust basis */
    }

    @media (max-width: 575.98px) {
        .fixture-card .card-body { padding: 0.7rem 0.6rem; }
        .team { flex-basis: 33%; } /* Adjust basis */
        .team-info { gap: 0.3rem; }
        .team-crest, .team-crest-placeholder { width: 22px; height: 22px; }
        .team-name { font-size: 0.8rem; }

        .vs-time { padding: 0 0.4rem; }
        .score-display { font-size: 1.1rem; letter-spacing: 0.5px;}
        .time { font-size: 0.85rem; }

        .fixture-card .card-footer { padding: 0.4rem 0.6rem; gap: 0.4rem;}
        .status-tag { font-size: 0.65rem; padding: 0.15rem 0.4rem; }
        .live-indicator { width: 6px; height: 6px; }
    }

</style>
@endpush