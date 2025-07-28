<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
// Ensure all your controllers are imported
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MatchDetailsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BettingController;
use App\Http\Controllers\TimeTestingController;

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

    Route::prefix('admin')->name('admin.')->group(function () {
            // In a real app, you would add an 'is_admin' middleware to this group
            Route::get('/time-test', [TimeTestingController::class, 'show'])->name('time.show');
            Route::post('/time-test/set', [TimeTestingController::class, 'set'])->name('time.set');
            Route::post('/time-test/reset', [TimeTestingController::class, 'reset'])->name('time.reset');
        });
});