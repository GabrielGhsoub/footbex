@extends('adminlte::page')

@section('title', 'My Weekly Bets')

@section('content_header')
    <h1><i class="fas fa-receipt mr-2"></i>My Weekly Bet Slips</h1>
@stop

@section('content')
<div class="card">
    <div class="card-body">
        @if (session('status'))
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <i class="icon fas fa-check"></i>{{ session('status') }}
            </div>
        @endif

        @if($slips->isEmpty())
            <div class="empty-state">
                <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                <p class="lead">You haven't placed any bets yet.</p>
                <a href="{{ route('betting.show_form') }}" class="btn btn-primary mt-2">
                    <i class="fas fa-plus-circle mr-1"></i>Place Bets for the Current Week
                </a>
            </div>
        @else
            <div class="accordion" id="slipsAccordion">
                @foreach($slips as $slip)
                    <div class="card slip-card card-info mb-3 {{ $loop->first ? '' : 'collapsed-card' }} {{ $slip->status == 'submitted' || $slip->status == 'processing' ? 'card-outline' : '' }}">
                        <div class="card-header slip-card-header" id="heading{{ $slip->id }}" data-card-widget="collapse">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left text-white" type="button">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                                        <div class="slip-title">
                                            <i class="fas fa-calendar-week mr-2"></i>
                                            Week: {{ $slip->week_identifier }}
                                            <small class="d-block d-sm-inline mt-1 mt-sm-0 ml-sm-2">
                                                (Submitted: {{ $slip->created_at->format('D, M j, Y') }})
                                            </small>
                                        </div>
                                        <div class="slip-tools">
                                            @if($slip->status === 'settled' && !is_null($slip->total_score))
                                                <span class="badge badge-light mr-2">
                                                    <i class="fas fa-star mr-1"></i>Total Score: {{ $slip->total_score }}
                                                </span>
                                            @endif
                                            <span class="badge status-badge
                                                @switch($slip->status)
                                                    @case('submitted') badge-warning @break
                                                    @case('processing') badge-info @break
                                                    @case('settled') badge-success @break
                                                    @default badge-secondary @break
                                                @endswitch">
                                                @switch($slip->status)
                                                    @case('submitted') <i class="fas fa-hourglass-half mr-1"></i> @break
                                                    @case('processing') <i class="fas fa-cogs mr-1"></i> @break
                                                    @case('settled') <i class="fas fa-check-circle mr-1"></i> @break
                                                @endswitch
                                                {{ ucfirst($slip->status) }}
                                            </span>
                                        </div>
                                    </div>
                                </button>
                            </h2>
                        </div>

                        <div id="collapse{{ $slip->id }}" class="collapse {{ $loop->first ? 'show' : '' }}" aria-labelledby="heading{{ $slip->id }}" data-parent="#slipsAccordion">
                            <div class="card-body p-0">
                                @if($slip->predictions->isNotEmpty())
                                    {{-- Desktop Table View --}}
                                    <div class="d-none d-md-block">
                                        <table class="table table-striped table-sm predictions-table">
                                            <thead>
                                                <tr>
                                                    <th>Match</th>
                                                    <th>Your Prediction</th>
                                                    <th>Actual Outcome</th>
                                                    <th class="text-center">Points</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($slip->predictions->sortBy('match_utc_date_time') as $prediction)
                                                    <tr>
                                                        <td class="match-cell">
                                                            <i class="fas fa-futbol text-muted mr-2"></i>{{ $prediction->home_team_name }} vs {{ $prediction->away_team_name }}
                                                        </td>
                                                        <td>
                                                            @if($prediction->actual_outcome && $prediction->points_awarded > 0)
                                                                <span class="text-success">✅</span>
                                                            @elseif($prediction->actual_outcome)
                                                                <span class="text-danger">❌</span>
                                                            @endif
                                                            {{ ucfirst(str_replace('_', ' ', $prediction->predicted_outcome)) }}
                                                        </td>
                                                        <td>
                                                            @if($prediction->actual_outcome)
                                                                {{ ucfirst(str_replace('_', ' ', $prediction->actual_outcome)) }}
                                                            @else
                                                                <span class="text-muted">Pending</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            @if(!is_null($prediction->actual_outcome))
                                                                <span class="badge badge-pill {{ $prediction->points_awarded > 0 ? 'badge-success' : 'badge-secondary' }}">{{ $prediction->points_awarded }}</span>
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    {{-- Mobile List View --}}
                                    <div class="d-md-none p-2">
                                        @foreach($slip->predictions->sortBy('match_utc_date_time') as $prediction)
                                            <div class="prediction-mobile-row">
                                                <div class="font-weight-bold match-title">{{ $prediction->home_team_name }} vs {{ $prediction->away_team_name }}</div>
                                                <div class="details-grid">
                                                    <div>
                                                        <small class="text-muted">Your Pick</small>
                                                        <div class="pick-value">
                                                            @if($prediction->actual_outcome && $prediction->points_awarded > 0)
                                                                <span class="text-success">✅</span>
                                                            @elseif($prediction->actual_outcome)
                                                                <span class="text-danger">❌</span>
                                                            @endif
                                                            {{ ucfirst(str_replace('_', ' ', $prediction->predicted_outcome)) }}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <small class="text-muted">Result</small>
                                                        <div>
                                                            @if($prediction->actual_outcome)
                                                                {{ ucfirst(str_replace('_', ' ', $prediction->actual_outcome)) }}
                                                            @else
                                                                <span class="text-muted">Pending</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="text-right">
                                                        <small class="text-muted">Points</small>
                                                        <div>
                                                            @if(!is_null($prediction->actual_outcome))
                                                                <span class="badge badge-pill {{ $prediction->points_awarded > 0 ? 'badge-success' : 'badge-secondary' }}">{{ $prediction->points_awarded }}</span>
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-center p-3">No predictions found for this slip.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="d-flex justify-content-center mt-3">
                {{ $slips->links() }}
            </div>
        @endif
    </div>
</div>
@stop

@push('css')
<style>
    /* --- Empty State --- */
    .empty-state {
        text-align: center;
        padding: 40px 20px;
    }

    /* --- Accordion & Card Header Styling --- */
    .slip-card .card-header {
        cursor: pointer;
        padding: 0;
    }
    .slip-card .btn-link {
        color: #fff;
        text-decoration: none;
    }
    .slip-card .btn-link:hover {
        color: #f0f0f0;
    }
    .slip-title {
        font-size: 1.1rem;
        font-weight: 600;
    }
    .slip-tools .badge {
        font-size: 0.9rem;
        padding: .4em .7em;
        vertical-align: middle;
    }
    /* Add icon for collapse state */
    .slip-card .btn-link:after {
        font-family: 'Font Awesome 5 Free';
        content: '\f077'; /* chevron-up */
        font-weight: 900;
        float: right;
        margin-left: 10px;
        transition: transform 0.2s ease-in-out;
    }
    .slip-card.collapsed-card .btn-link:after {
        transform: rotate(180deg);
    }

    /* --- Desktop Table Styling --- */
    .predictions-table th {
        border-top: 0;
        color: #495057;
        font-weight: 600;
    }
    .predictions-table td {
        vertical-align: middle;
    }
    .predictions-table .match-cell {
        font-weight: 500;
    }

    /* --- Mobile List Styling --- */
    .prediction-mobile-row {
        padding: 12px 10px;
        border-bottom: 1px solid #e9ecef;
    }
    .prediction-mobile-row:last-child {
        border-bottom: none;
    }
    .prediction-mobile-row .match-title {
        margin-bottom: 8px;
    }
    .prediction-mobile-row .details-grid {
        display: grid;
        grid-template-columns: 2fr 2fr 1fr;
        gap: 10px;
        align-items: center;
    }
    .prediction-mobile-row .pick-value {
        font-weight: 500;
    }
</style>
@endpush