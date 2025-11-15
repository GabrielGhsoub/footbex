@extends('adminlte::page')

@section('title', 'Double Points Management')

@section('content_header')
    <h1><i class="fas fa-bolt mr-2"></i>Double Points Management - Week {{ $weekIdentifier }}</h1>
@stop

@section('content')
<div class="container-fluid">
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

    {{-- Set Weekly Match --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary">
            <h3 class="card-title"><i class="fas fa-star mr-2"></i>Weekly Double Point Match</h3>
        </div>
        <div class="card-body">
            @if($existingMatch)
                <div class="alert alert-success mb-3">
                    <i class="fas fa-check-circle mr-2"></i>
                    <strong>Current Selection:</strong> {{ $existingMatch->home_team_name }} vs {{ $existingMatch->away_team_name }}
                </div>
            @endif

            <form id="setMatchForm" method="POST" action="{{ route('admin.double_point.store_match') }}">
                @csrf
                <input type="hidden" name="week_identifier" value="{{ $weekIdentifier }}">

                <div class="form-group">
                    <label><i class="fas fa-futbol mr-1"></i>Select Match <span class="text-danger">*</span></label>
                    @if($message)
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-2"></i>{{ $message }}
                        </div>
                    @elseif(empty($matches))
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-2"></i>No matches available for this week.
                        </div>
                    @else
                        <select class="form-control" id="matchSelect" name="match_select" required>
                            <option value="">-- Select a match --</option>
                            @foreach($matches as $match)
                                <option value="{{ $match['id'] }}"
                                        data-home="{{ $match['home']['name'] }}"
                                        data-away="{{ $match['away']['name'] }}"
                                        @if($existingMatch && $existingMatch->match_id == $match['id']) selected @endif>
                                    {{ $match['home']['name'] }} vs {{ $match['away']['name'] }}
                                    ({{ \Carbon\Carbon::parse($match['utcDate'])->format('M j, H:i') }})
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="match_id" id="match_id">
                        <input type="hidden" name="home_team_name" id="home_team_name">
                        <input type="hidden" name="away_team_name" id="away_team_name">
                    @endif
                </div>

                @if(!empty($matches))
                    <button type="submit" class="btn btn-primary" id="submitBtn" @if(!$existingMatch) disabled @endif>
                        <i class="fas fa-bolt mr-2"></i>{{ $existingMatch ? 'Update' : 'Set' }} Double Point Match
                    </button>
                @endif
            </form>
        </div>
    </div>

    {{-- Manage User Opt-In Requests --}}
    <div class="card shadow-sm">
        <div class="card-header bg-info">
            <h3 class="card-title"><i class="fas fa-users mr-2"></i>User Opt-In Requests</h3>
        </div>
        <div class="card-body">
            @if($requests->isEmpty())
                <div class="empty-state text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="lead">No double point opt-in requests yet for this week.</p>
                    <p class="text-muted">Users will see the double point opportunity after you set the match above.</p>
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
    .empty-state {
        padding: 40px 20px;
    }
    .nav-tabs .badge {
        font-size: 0.75rem;
    }
</style>
@endpush
