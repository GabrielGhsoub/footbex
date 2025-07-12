<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
// Ensure all your controllers are imported
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MatchDetailsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BettingController;

// Authentication routes (no 'auth' middleware applied here)
Auth::routes();

// Apply 'auth' middleware to all other routes
Route::group(['middleware' => 'auth'], function () {
    // Welcome page / Dashboard
    // Route::view('/', 'welcome'); // OLD WAY
    Route::get('/', [HomeController::class, 'showWelcomeDashboard'])->name('welcome'); // NEW WAY

    // Primary view for matches overview
    Route::view('/matches', 'matches')->name('matches.index'); // Renamed for consistency if you use named routes

    // API endpoint for matches (if still used by frontend components)
    Route::get('/api/matches', [HomeController::class, 'index'])->name('home'); // Changed 'home' to be more descriptive

    // Detail view for individual matches
    Route::get('/match-details/{matchId}', [MatchDetailsController::class, 'show'])->name('match.details');

    // Profile viewing and updating
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show'); // Renamed for consistency
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // New Betting Routes for Weekly Pool
    Route::get('/weekly-bet', [BettingController::class, 'showCurrentWeekBetForm'])->name('betting.show_form');
    Route::post('/weekly-bet', [BettingController::class, 'storeCurrentWeekBet'])->name('betting.store');
    Route::get('/my-bets', [BettingController::class, 'myBets'])->name('betting.my_bets');

    // If you have a separate /dashboard route that uses HomeController@dashboard
    // Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');
    // Ensure it doesn't conflict or serves its intended purpose.
    // The request is about the page at '/', which we now point to showWelcomeDashboard.
});