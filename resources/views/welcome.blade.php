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
            <div class="card-body p-0">
                @if($leaderboardUsers && $leaderboardUsers->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="leaderboard-table">
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
                                        <td>{{ ($leaderboardUsers->currentPage() - 1) * $leaderboardUsers->perPage() + $loop->iteration }}.</td>
                                        <td>
                                            <i class="fas fa-user mr-2"></i>{{ $entry->name }}
                                            @if($loop->first && $leaderboardUsers->currentPage() == 1) 
                                                <span class="badge bg-warning ml-2">Winner!</span> 
                                            @endif
                                            @if(($loop->iteration == 2) && $leaderboardUsers->currentPage() == 1) 
                                                <span class="badge bg-secondary ml-2">2nd</span> 
                                            @endif
                                            @if(($loop->iteration == 3) && $leaderboardUsers->currentPage() == 1) 
                                                <span class="badge bg-info ml-2">3rd</span> 
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-success">{{ $entry->total_points ?? 0 }} Pts</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Infinite scroll loader --}}
                    <div id="load-more" class="text-center p-3">
                        <button class="btn btn-outline-primary" id="load-more-btn" 
                            data-next-page="{{ $leaderboardUsers->nextPageUrl() }}">
                            Load More
                        </button>
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
    </div>
</div>
@stop

@push('css')
<style>
    .display-4 {
        font-size: 3.5rem;
    }
    #load-more-btn {
        border-radius: 20px;
        padding: 8px 20px;
    }
</style>
@endpush

@push('js')
<script>
document.addEventListener("DOMContentLoaded", function () {
    let loadBtn = document.getElementById("load-more-btn");
    let tableBody = document.querySelector("#leaderboard-table tbody");

    if (loadBtn) {
        loadBtn.addEventListener("click", function () {
            let nextPageUrl = loadBtn.getAttribute("data-next-page");
            if (!nextPageUrl) {
                loadBtn.innerText = "No more results";
                loadBtn.disabled = true;
                return;
            }

            loadBtn.innerText = "Loading...";

            fetch(nextPageUrl)
                .then(res => res.text())
                .then(data => {
                    // Extract only the rows from returned HTML
                    let parser = new DOMParser();
                    let doc = parser.parseFromString(data, "text/html");
                    let newRows = doc.querySelectorAll("#leaderboard-table tbody tr");

                    newRows.forEach(row => tableBody.appendChild(row));

                    // Update next page URL
                    let newBtn = doc.querySelector("#load-more-btn");
                    if (newBtn) {
                        loadBtn.setAttribute("data-next-page", newBtn.getAttribute("data-next-page"));
                        loadBtn.innerText = "Load More";
                    } else {
                        loadBtn.innerText = "No more results";
                        loadBtn.disabled = true;
                    }
                })
                .catch(err => {
                    console.error(err);
                    loadBtn.innerText = "Error. Try again.";
                });
        });
    }
});
</script>
@endpush
