@extends('adminlte::page')

@section('title', 'Double Point Requests')

@section('content_header')
    <h1><i class="fas fa-bolt mr-2"></i>Double Point Requests Management</h1>
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

        @if($requests->isEmpty())
            <div class="empty-state text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <p class="lead">No double point requests found.</p>
            </div>
        @else
            {{-- Filter Tabs --}}
            <ul class="nav nav-tabs mb-3" id="requestTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="pending-tab" data-toggle="tab" href="#pending" role="tab">
                        <i class="fas fa-clock mr-1"></i>Pending
                        <span class="badge badge-warning ml-1">{{ $requests->where('status', 'pending')->count() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="approved-tab" data-toggle="tab" href="#approved" role="tab">
                        <i class="fas fa-check-circle mr-1"></i>Approved
                        <span class="badge badge-success ml-1">{{ $requests->where('status', 'approved')->count() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="rejected-tab" data-toggle="tab" href="#rejected" role="tab">
                        <i class="fas fa-times-circle mr-1"></i>Rejected
                        <span class="badge badge-danger ml-1">{{ $requests->where('status', 'rejected')->count() }}</span>
                    </a>
                </li>
            </ul>

            <div class="tab-content" id="requestTabsContent">
                {{-- Pending Requests --}}
                <div class="tab-pane fade show active" id="pending" role="tabpanel">
                    @if($requests->where('status', 'pending')->isEmpty())
                        <p class="text-muted text-center py-3">No pending requests.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>User</th>
                                        <th>Gameweek</th>
                                        <th>Match</th>
                                        <th>Prediction</th>
                                        <th>Submitted</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($requests->where('status', 'pending') as $request)
                                        <tr>
                                            <td>{{ $request->user->name }}</td>
                                            <td>
                                                @php
                                                    $gameweek = null;
                                                    $startYear = 2025;
                                                    $startWeekOfYear = 33;

                                                    if (str_contains($request->week_identifier, '-')) {
                                                        list($slipYear, $slipWeek) = array_map('intval', explode('-', $request->week_identifier));

                                                        if ($slipYear === $startYear && $slipWeek >= $startWeekOfYear) {
                                                            $gameweek = ($slipWeek - $startWeekOfYear) + 1;
                                                        } elseif ($slipYear > $startYear) {
                                                            $gameweek = (52 - $startWeekOfYear) + 1 + $slipWeek;
                                                        }
                                                    }
                                                @endphp
                                                @if($gameweek)
                                                    Gameweek {{ $gameweek }}
                                                @else
                                                    {{ $request->week_identifier }}
                                                @endif
                                            </td>
                                            <td>
                                                <i class="fas fa-futbol text-muted mr-1"></i>
                                                {{ $request->prediction->home_team_name }} vs {{ $request->prediction->away_team_name }}
                                            </td>
                                            <td>
                                                <span class="badge badge-info">
                                                    {{ ucfirst(str_replace('_', ' ', $request->prediction->predicted_outcome)) }}
                                                </span>
                                            </td>
                                            <td>{{ $request->created_at->diffForHumans() }}</td>
                                            <td class="text-center">
                                                <form action="{{ route('admin.double_point.approve', $request->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#rejectModal{{ $request->id }}" title="Reject">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>

                                                {{-- Reject Modal --}}
                                                <div class="modal fade" id="rejectModal{{ $request->id }}" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form action="{{ route('admin.double_point.reject', $request->id) }}" method="POST">
                                                                @csrf
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Reject Double Point Request</h5>
                                                                    <button type="button" class="close" data-dismiss="modal">
                                                                        <span>&times;</span>
                                                                    </button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p>Rejecting request from <strong>{{ $request->user->name }}</strong> for:</p>
                                                                    <p class="text-muted">{{ $request->prediction->home_team_name }} vs {{ $request->prediction->away_team_name }}</p>
                                                                    <div class="form-group">
                                                                        <label for="rejection_reason{{ $request->id }}">Reason (optional):</label>
                                                                        <textarea name="rejection_reason" id="rejection_reason{{ $request->id }}" class="form-control" rows="3"></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-danger">Reject Request</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- Approved Requests --}}
                <div class="tab-pane fade" id="approved" role="tabpanel">
                    @if($requests->where('status', 'approved')->isEmpty())
                        <p class="text-muted text-center py-3">No approved requests.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>User</th>
                                        <th>Gameweek</th>
                                        <th>Match</th>
                                        <th>Prediction</th>
                                        <th>Approved By</th>
                                        <th>Approved At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($requests->where('status', 'approved') as $request)
                                        <tr>
                                            <td>{{ $request->user->name }}</td>
                                            <td>
                                                @php
                                                    $gameweek = null;
                                                    $startYear = 2025;
                                                    $startWeekOfYear = 33;

                                                    if (str_contains($request->week_identifier, '-')) {
                                                        list($slipYear, $slipWeek) = array_map('intval', explode('-', $request->week_identifier));

                                                        if ($slipYear === $startYear && $slipWeek >= $startWeekOfYear) {
                                                            $gameweek = ($slipWeek - $startWeekOfYear) + 1;
                                                        } elseif ($slipYear > $startYear) {
                                                            $gameweek = (52 - $startWeekOfYear) + 1 + $slipWeek;
                                                        }
                                                    }
                                                @endphp
                                                @if($gameweek)
                                                    Gameweek {{ $gameweek }}
                                                @else
                                                    {{ $request->week_identifier }}
                                                @endif
                                            </td>
                                            <td>
                                                <i class="fas fa-futbol text-muted mr-1"></i>
                                                {{ $request->prediction->home_team_name }} vs {{ $request->prediction->away_team_name }}
                                            </td>
                                            <td>
                                                <span class="badge badge-info">
                                                    {{ ucfirst(str_replace('_', ' ', $request->prediction->predicted_outcome)) }}
                                                </span>
                                            </td>
                                            <td>{{ $request->approver->name ?? 'N/A' }}</td>
                                            <td>{{ $request->approved_at ? $request->approved_at->format('M j, Y H:i') : 'N/A' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- Rejected Requests --}}
                <div class="tab-pane fade" id="rejected" role="tabpanel">
                    @if($requests->where('status', 'rejected')->isEmpty())
                        <p class="text-muted text-center py-3">No rejected requests.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>User</th>
                                        <th>Gameweek</th>
                                        <th>Match</th>
                                        <th>Prediction</th>
                                        <th>Rejected By</th>
                                        <th>Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($requests->where('status', 'rejected') as $request)
                                        <tr>
                                            <td>{{ $request->user->name }}</td>
                                            <td>
                                                @php
                                                    $gameweek = null;
                                                    $startYear = 2025;
                                                    $startWeekOfYear = 33;

                                                    if (str_contains($request->week_identifier, '-')) {
                                                        list($slipYear, $slipWeek) = array_map('intval', explode('-', $request->week_identifier));

                                                        if ($slipYear === $startYear && $slipWeek >= $startWeekOfYear) {
                                                            $gameweek = ($slipWeek - $startWeekOfYear) + 1;
                                                        } elseif ($slipYear > $startYear) {
                                                            $gameweek = (52 - $startWeekOfYear) + 1 + $slipWeek;
                                                        }
                                                    }
                                                @endphp
                                                @if($gameweek)
                                                    Gameweek {{ $gameweek }}
                                                @else
                                                    {{ $request->week_identifier }}
                                                @endif
                                            </td>
                                            <td>
                                                <i class="fas fa-futbol text-muted mr-1"></i>
                                                {{ $request->prediction->home_team_name }} vs {{ $request->prediction->away_team_name }}
                                            </td>
                                            <td>
                                                <span class="badge badge-info">
                                                    {{ ucfirst(str_replace('_', ' ', $request->prediction->predicted_outcome)) }}
                                                </span>
                                            </td>
                                            <td>{{ $request->approver->name ?? 'N/A' }}</td>
                                            <td>{{ $request->rejection_reason ?? 'No reason provided' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
@stop

@push('css')
<style>
    .empty-state {
        padding: 40px 20px;
    }
    .nav-tabs .badge {
        font-size: 0.75rem;
    }
</style>
@endpush
