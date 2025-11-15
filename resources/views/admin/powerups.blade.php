@extends('adminlte::page')

@section('title', 'Power-Ups Management')

@section('content_header')
    <h1>
        <i class="fas fa-bolt mr-2"></i>Power-Ups Management -
        @if($gameweek)
            Gameweek {{ $gameweek }}
        @elseif($upcomingGameweek)
            Upcoming: Gameweek {{ $upcomingGameweek }}
        @else
            Week {{ $weekIdentifier }}
        @endif
    </h1>
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

    @if($gameweek && $gameweek < 20)
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-2"></i>
            <strong>Power-Ups are available from Gameweek 20 onwards.</strong>
            <br>Current: Gameweek {{ $gameweek }}. Power-Ups will unlock at Gameweek 20.
        </div>
    @else
    {{-- Power-Ups are only available from Gameweek 20 onwards --}}

    {{-- Double Point Match --}}
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

            <form method="POST" action="{{ route('admin.powerups.set_match') }}">
                @csrf
                <input type="hidden" name="week_identifier" value="{{ $weekIdentifier }}">

                <div class="form-group">
                    <label><i class="fas fa-futbol mr-1"></i>Select Match</label>
                    @if($message)
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-2"></i>{{ $message }}
                        </div>
                    @elseif(empty($matches))
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-2"></i>No matches available for this week.
                        </div>
                    @else
                        <select class="form-control" id="matchSelect" name="match_select" required onchange="updateMatchFields()">
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
                        <input type="hidden" name="match_id" id="match_id" value="{{ $existingMatch->match_id ?? '' }}">
                        <input type="hidden" name="home_team_name" id="home_team_name" value="{{ $existingMatch->home_team_name ?? '' }}">
                        <input type="hidden" name="away_team_name" id="away_team_name" value="{{ $existingMatch->away_team_name ?? '' }}">
                    @endif
                </div>

                @if(!empty($matches))
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-bolt mr-2"></i>{{ $existingMatch ? 'Update' : 'Set' }} Match
                    </button>
                @endif
            </form>
        </div>
    </div>

    {{-- Double Point Opt-In Requests --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-info">
            <h3 class="card-title">
                <i class="fas fa-users mr-2"></i>Double Point Opt-In Requests
                @if($gameweek)
                    (Gameweek {{ $gameweek }})
                @else
                    (Week {{ $weekIdentifier }})
                @endif
            </h3>
        </div>
        <div class="card-body">
            @if($doublePointRequests->isEmpty())
                <p class="text-muted text-center py-3">No double point opt-in requests for this week.</p>
            @else
                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#dp-pending">
                            Pending <span class="badge badge-warning">{{ $doublePointRequests->where('status', 'pending')->count() }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#dp-approved">
                            Approved <span class="badge badge-success">{{ $doublePointRequests->where('status', 'approved')->count() }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#dp-rejected">
                            Rejected <span class="badge badge-danger">{{ $doublePointRequests->where('status', 'rejected')->count() }}</span>
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    {{-- Pending --}}
                    <div class="tab-pane fade show active" id="dp-pending">
                        @if($doublePointRequests->where('status', 'pending')->isEmpty())
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
                                        @foreach($doublePointRequests->where('status', 'pending') as $req)
                                            <tr>
                                                <td>{{ $req->user->name }}</td>
                                                <td>{{ $req->prediction->home_team_name }} vs {{ $req->prediction->away_team_name }}</td>
                                                <td><span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $req->prediction->predicted_outcome)) }}</span></td>
                                                <td>{{ $req->created_at->diffForHumans() }}</td>
                                                <td class="text-center">
                                                    <form action="{{ route('admin.powerups.approve_double_point', $req->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success">
                                                            <i class="fas fa-check"></i> Approve
                                                        </button>
                                                    </form>
                                                    <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#rejectDPModal{{ $req->id }}">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>

                                                    {{-- Reject Modal --}}
                                                    <div class="modal fade" id="rejectDPModal{{ $req->id }}" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <form action="{{ route('admin.powerups.reject_double_point', $req->id) }}" method="POST">
                                                                    @csrf
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Reject Request</h5>
                                                                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <p>Rejecting request from <strong>{{ $req->user->name }}</strong></p>
                                                                        <div class="form-group">
                                                                            <label>Reason (optional):</label>
                                                                            <textarea name="rejection_reason" class="form-control" rows="3"></textarea>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                        <button type="submit" class="btn btn-danger">Reject</button>
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

                    {{-- Approved --}}
                    <div class="tab-pane fade" id="dp-approved">
                        @if($doublePointRequests->where('status', 'approved')->isEmpty())
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
                                        @foreach($doublePointRequests->where('status', 'approved') as $req)
                                            <tr>
                                                <td>{{ $req->user->name }}</td>
                                                <td>{{ $req->prediction->home_team_name }} vs {{ $req->prediction->away_team_name }}</td>
                                                <td><span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $req->prediction->predicted_outcome)) }}</span></td>
                                                <td>{{ $req->approver->name ?? 'N/A' }}</td>
                                                <td>{{ $req->approved_at ? $req->approved_at->format('M j, Y H:i') : 'N/A' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    {{-- Rejected --}}
                    <div class="tab-pane fade" id="dp-rejected">
                        @if($doublePointRequests->where('status', 'rejected')->isEmpty())
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
                                        @foreach($doublePointRequests->where('status', 'rejected') as $req)
                                            <tr>
                                                <td>{{ $req->user->name }}</td>
                                                <td>{{ $req->prediction->home_team_name }} vs {{ $req->prediction->away_team_name }}</td>
                                                <td><span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $req->prediction->predicted_outcome)) }}</span></td>
                                                <td>{{ $req->approver->name ?? 'N/A' }}</td>
                                                <td>{{ $req->rejection_reason ?? 'No reason provided' }}</td>
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

    {{-- Gameweek Boost Requests --}}
    <div class="card shadow-sm">
        <div class="card-header bg-warning">
            <h3 class="card-title"><i class="fas fa-rocket mr-2"></i>Gameweek Boost Requests</h3>
        </div>
        <div class="card-body">
            @if($boostRequests->isEmpty())
                <p class="text-muted text-center py-3">No gameweek boost requests yet.</p>
            @else
                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#boost-pending">
                            Pending <span class="badge badge-warning">{{ $boostRequests->where('status', 'pending')->count() }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#boost-approved">
                            Approved <span class="badge badge-success">{{ $boostRequests->where('status', 'approved')->count() }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#boost-rejected">
                            Rejected <span class="badge badge-danger">{{ $boostRequests->where('status', 'rejected')->count() }}</span>
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    {{-- Pending --}}
                    <div class="tab-pane fade show active" id="boost-pending">
                        @if($boostRequests->where('status', 'pending')->isEmpty())
                            <p class="text-muted text-center py-3">No pending requests.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>User</th>
                                            <th>Gameweek</th>
                                            <th>Submitted</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($boostRequests->where('status', 'pending') as $req)
                                            <tr>
                                                <td>{{ $req->user->name }}</td>
                                                <td>{{ $req->week_identifier }}</td>
                                                <td>{{ $req->created_at->diffForHumans() }}</td>
                                                <td class="text-center">
                                                    <form action="{{ route('admin.powerups.approve_boost', $req->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success">
                                                            <i class="fas fa-check"></i> Approve
                                                        </button>
                                                    </form>
                                                    <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#rejectBoostModal{{ $req->id }}">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>

                                                    {{-- Reject Modal --}}
                                                    <div class="modal fade" id="rejectBoostModal{{ $req->id }}" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <form action="{{ route('admin.powerups.reject_boost', $req->id) }}" method="POST">
                                                                    @csrf
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Reject Boost Request</h5>
                                                                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <p>Rejecting boost request from <strong>{{ $req->user->name }}</strong> for week {{ $req->week_identifier }}</p>
                                                                        <div class="form-group">
                                                                            <label>Reason (optional):</label>
                                                                            <textarea name="rejection_reason" class="form-control" rows="3"></textarea>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                        <button type="submit" class="btn btn-danger">Reject</button>
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

                    {{-- Approved --}}
                    <div class="tab-pane fade" id="boost-approved">
                        @if($boostRequests->where('status', 'approved')->isEmpty())
                            <p class="text-muted text-center py-3">No approved requests.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>User</th>
                                            <th>Gameweek</th>
                                            <th>Approved By</th>
                                            <th>Approved At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($boostRequests->where('status', 'approved') as $req)
                                            <tr>
                                                <td>{{ $req->user->name }}</td>
                                                <td>{{ $req->week_identifier }}</td>
                                                <td>{{ $req->approver->name ?? 'N/A' }}</td>
                                                <td>{{ $req->approved_at ? $req->approved_at->format('M j, Y H:i') : 'N/A' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    {{-- Rejected --}}
                    <div class="tab-pane fade" id="boost-rejected">
                        @if($boostRequests->where('status', 'rejected')->isEmpty())
                            <p class="text-muted text-center py-3">No rejected requests.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>User</th>
                                            <th>Gameweek</th>
                                            <th>Rejected By</th>
                                            <th>Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($boostRequests->where('status', 'rejected') as $req)
                                            <tr>
                                                <td>{{ $req->user->name }}</td>
                                                <td>{{ $req->week_identifier }}</td>
                                                <td>{{ $req->approver->name ?? 'N/A' }}</td>
                                                <td>{{ $req->rejection_reason ?? 'No reason provided' }}</td>
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

    @endif {{-- End gameweek >= 20 check --}}
</div>
@stop

@push('js')
<script>
function updateMatchFields() {
    const select = document.getElementById('matchSelect');
    const option = select.options[select.selectedIndex];

    if (option.value) {
        document.getElementById('match_id').value = option.value;
        document.getElementById('home_team_name').value = option.getAttribute('data-home');
        document.getElementById('away_team_name').value = option.getAttribute('data-away');
    }
}
</script>
@endpush
