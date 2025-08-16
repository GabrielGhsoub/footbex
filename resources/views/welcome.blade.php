@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Dashboard</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Weekly Betting Leaderboard</h3>
                </div>
                {{-- The p-0 class is removed from card-body to give the scrollbar some padding --}}
                <div class="card-body">
                    @if($leaderboardUsers && $leaderboardUsers->count() > 0)
                        {{-- This new div will handle the scrolling --}}
                        <div class="leaderboard-scroll">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 10px">#</th>
                                        <th>User</th>
                                        <th>Total Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($leaderboardUsers as $index => $entry)
                                        <tr>
                                            <td>{{ $index + 1 }}.</td>
                                            <td>
                                                <i class="fas fa-user mr-2"></i>{{ $entry->name }}
                                                @if($index == 0) <span class="badge bg-warning ml-2">Winner!</span> @endif
                                                @if($index == 1) <span class="badge bg-secondary ml-2">2nd</span> @endif
                                                @if($index == 2) <span class="badge bg-info ml-2">3rd</span> @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-success">{{ $entry->total_points ?? 0 }} Pts</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-center p-3">No leaderboard data available yet. Bets might still be processing or no bets have been settled.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title">Current Pool Prize</h3>
                </div>
                <div class="card-body text-center">
                    <h2 class="display-4 font-weight-bold">${{ number_format($poolPrize, 2) }}</h2>
                    <p class="text-muted">Winner takes all!</p>
                </div>
            </div>

            {{-- Placeholder for other dashboard items from your original view --}}
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">Quick Links</h3>
                </div>
                <div class="card-body">
                    <p><a href="{{ route('betting.show_form') }}">Place This Week's Bets</a></p>
                    <p><a href="{{ route('betting.my_bets') }}">View My Bets</a></p>
                    <p><a href="{{ route('matches.index') }}">Upcoming Matches</a></p>
                </div>
            </div>
            
            {{-- You can add your "- Matches of today", "- Betting of today", "- Total betting graphs" sections here --}}
            {{-- For "Matches of today", you'd fetch them in HomeController and pass to this view --}}

        </div>
    </div>
@stop

@push('css')
<style>
    .display-4 {
        font-size: 3.5rem; /* Or your preferred size */
    }

    /* NEW CSS FOR SCROLLABLE LEADERBOARD */
    .leaderboard-scroll {
        max-height: 550px; /* Sets a maximum height. Adjust this value as needed. */
        overflow-y: auto;  /* Adds a vertical scrollbar only when the content overflows. */
    }
</style>
@endpush

@push('js')
    {{-- Add JS here if you need charts or dynamic updates later --}}
@endpush