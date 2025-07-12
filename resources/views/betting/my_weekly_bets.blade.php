@extends('adminlte::page') {{-- Or your main layout --}}

@section('title', 'My Weekly Bets')

@section('content_header')
    <h1>My Weekly Bet Slips</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            @if (session('status'))
                <div class="alert alert-success alert-dismissible">
                     <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    {{ session('status') }}
                </div>
            @endif

            @if($slips->isEmpty())
                <p>You have not placed any weekly bets yet.</p>
                <a href="{{ route('betting.show_form') }}" class="btn btn-primary">Place Bets for Current Week</a>
            @else
                @foreach($slips as $slip)
                    <div class="card card-info mb-3 {{ $slip->status == 'submitted' || $slip->status == 'processing' ? 'card-outline' : '' }}">
                        <div class="card-header">
                            <h3 class="card-title">
                                Week: {{ $slip->week_identifier }} 
                                <small>(Submitted: {{ $slip->created_at->format('D, M j, Y H:i') }})</small>
                            </h3>
                            <div class="card-tools">
                                <span class="badge 
                                    @switch($slip->status)
                                        @case('submitted') badge-warning @break
                                        @case('processing') badge-info @break
                                        @case('settled') badge-success @break
                                        @case('open') badge-secondary @break
                                        @default badge-light @break
                                    @endswitch
                                ">
                                    Status: {{ ucfirst($slip->status) }}
                                </span>
                                @if($slip->status === 'settled' && !is_null($slip->total_score))
                                    <span class="badge badge-primary">Total Score: {{ $slip->total_score }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="card-body p-0">
                            @if($slip->predictions->isNotEmpty())
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Match</th>
                                            <th>Your Prediction</th>
                                            <th>Actual Outcome</th>
                                            <th>Points</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($slip->predictions->sortBy('match_utc_date_time') as $prediction)
                                            <tr>
                                                <td>{{ $prediction->home_team_name }} vs {{ $prediction->away_team_name }}</td>
                                                <td>{{ ucfirst(str_replace('_', ' ', $prediction->predicted_outcome)) }}</td>
                                                <td>
                                                    @if($prediction->actual_outcome)
                                                        {{ ucfirst(str_replace('_', ' ', $prediction->actual_outcome)) }}
                                                    @else
                                                        <span class="text-muted">Pending</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    {{-- ---- MODIFIED CONDITION HERE ---- --}}
                                                    @if(!is_null($prediction->actual_outcome) && !in_array(strtolower($prediction->actual_outcome), ['pending', 'timed', 'scheduled'])) 
                                                        {{-- Show points if the prediction itself has been processed (has an actual_outcome) --}}
                                                        {{-- and actual_outcome is not a pending-like status itself --}}
                                                        {{ $prediction->points_awarded }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <p class="text-center p-3">No predictions found for this slip.</p>
                            @endif
                        </div>
                    </div>
                @endforeach
                <div class="d-flex justify-content-center">
                    {{ $slips->links() }}
                </div>
            @endif
        </div>
    </div>
@stop