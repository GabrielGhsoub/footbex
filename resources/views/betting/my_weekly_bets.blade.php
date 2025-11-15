@extends('adminlte::page')

@section('title', 'My Weekly Bets')

@section('content_header')
    <h1><i class="fas fa-receipt mr-2"></i>My Weekly Bet Slips</h1>
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
                    <div class="card slip-card mb-3 border-0 shadow-sm">
                        <div class="card-header bg-info text-white p-0" id="heading{{ $slip->id }}">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left text-white d-flex justify-content-between align-items-center"
                                        type="button"
                                        data-toggle="collapse"
                                        data-target="#collapse{{ $slip->id }}"
                                        aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                                        aria-controls="collapse{{ $slip->id }}">
                                    <div>
                                        <i class="fas fa-calendar-week mr-2"></i>

                                        @if($slip->actual_gameweek)
                                            Gameweek {{ $slip->actual_gameweek }}
                                        @else
                                            Week: {{ $slip->week_identifier }}
                                        @endif

                                        <small class="d-block d-sm-inline ml-sm-2">
                                            (Submitted: {{ $slip->created_at->format('D, M j, Y') }})
                                        </small>
                                    </div>
                                    <div class="slip-tools text-right">
                                        @if($slip->status === 'settled' && !is_null($slip->total_score))
                                            <span class="badge badge-light mr-2">
                                                <i class="fas fa-star mr-1"></i>Total Score: {{ $slip->total_score }}
                                            </span>
                                        @endif
                                        <span class="badge
                                            @switch($slip->status)
                                                @case('open') badge-warning @break
                                                @case('processing') badge-info @break
                                                @case('settled') badge-success @break
                                                @default badge-secondary @break
                                            @endswitch">
                                            @switch($slip->status)
                                                @case('open') <i class="fas fa-hourglass-half mr-1"></i> @break
                                                @case('processing') <i class="fas fa-cogs mr-1"></i> @break
                                                @case('settled') <i class="fas fa-check-circle mr-1"></i> @break
                                            @endswitch
                                            {{ ucfirst($slip->status) }}
                                        </span>
                                        <i class="fas fa-chevron-down ml-2 toggle-icon"></i>
                                    </div>
                                </button>
                            </h2>
                        </div>

                        <div id="collapse{{ $slip->id }}"
                             class="collapse {{ $loop->first ? 'show' : '' }}"
                             aria-labelledby="heading{{ $slip->id }}"
                             data-parent="#slipsAccordion">
                            <div class="card-body p-0">
                                @if($slip->predictions->isNotEmpty())
                                    {{-- Desktop Table --}}
                                    <div class="d-none d-md-block">
                                        <table class="table table-striped table-sm mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Match</th>
                                                    <th>Your Prediction</th>
                                                    <th>Actual Outcome</th>
                                                    <th class="text-center">Points</th>
                                                    <th class="text-center">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($slip->predictions->sortBy('match_utc_date_time') as $prediction)
                                                    <tr>
                                                        <td><i class="fas fa-futbol text-muted mr-2"></i>{{ $prediction->home_team_name }} vs {{ $prediction->away_team_name }}</td>
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
                                                        <td class="text-center">
                                                            @if($prediction->is_double_points)
                                                                <span class="badge badge-warning" title="Double Points Active">
                                                                    <i class="fas fa-bolt mr-1"></i>DOUBLE
                                                                </span>
                                                            @elseif($prediction->doublePointRequest)
                                                                @if($prediction->doublePointRequest->status === 'pending')
                                                                    <span class="badge badge-info" title="Awaiting Admin Approval">
                                                                        <i class="fas fa-clock mr-1"></i>Pending
                                                                    </span>
                                                                @elseif($prediction->doublePointRequest->status === 'rejected')
                                                                    <span class="badge badge-danger" title="Request Rejected">
                                                                        <i class="fas fa-times mr-1"></i>Rejected
                                                                    </span>
                                                                @endif
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    {{-- Mobile List --}}
                                    <div class="d-md-none p-2">
                                        @foreach($slip->predictions->sortBy('match_utc_date_time') as $prediction)
                                            <div class="prediction-mobile-row">
                                                <div class="font-weight-bold">
                                                    {{ $prediction->home_team_name }} vs {{ $prediction->away_team_name }}
                                                    @if($prediction->is_double_points)
                                                        <span class="badge badge-warning ml-2" title="Double Points Active">
                                                            <i class="fas fa-bolt mr-1"></i>DOUBLE
                                                        </span>
                                                    @elseif($prediction->doublePointRequest)
                                                        @if($prediction->doublePointRequest->status === 'pending')
                                                            <span class="badge badge-info ml-2" title="Awaiting Admin Approval">
                                                                <i class="fas fa-clock mr-1"></i>Pending
                                                            </span>
                                                        @elseif($prediction->doublePointRequest->status === 'rejected')
                                                            <span class="badge badge-danger ml-2" title="Request Rejected">
                                                                <i class="fas fa-times mr-1"></i>Rejected
                                                            </span>
                                                        @endif
                                                    @endif
                                                </div>
                                                <div class="details-grid">
                                                    <div>
                                                        <small class="text-muted">Your Pick</small>
                                                        <div>
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
                                    <p class="text-center p-3 mb-0">No predictions found for this slip.</p>
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
    .empty-state {
        text-align: center;
        padding: 40px 20px;
    }
    .slip-card .btn-link {
        text-decoration: none;
        font-weight: 600;
    }
    .slip-tools .badge {
        font-size: 0.85rem;
    }
    .toggle-icon {
        transition: transform 0.3s ease;
    }
    .collapsed .toggle-icon {
        transform: rotate(-90deg);
    }
    .prediction-mobile-row {
        border-bottom: 1px solid #e9ecef;
        padding: 10px 0;
    }
    .prediction-mobile-row:last-child {
        border-bottom: none;
    }
    .details-grid {
        display: grid;
        grid-template-columns: 2fr 2fr 1fr;
        gap: 10px;
    }
</style>
@endpush

@push('js')
<script>
    // Rotate chevron on collapse toggle
    $('#slipsAccordion').on('show.bs.collapse', function (e) {
        $(e.target).prev('.card-header').find('.toggle-icon').css('transform', 'rotate(0deg)');
    });
    $('#slipsAccordion').on('hide.bs.collapse', function (e) {
        $(e.target).prev('.card-header').find('.toggle-icon').css('transform', 'rotate(-90deg)');
    });
</script>
@endpush