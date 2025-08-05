<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MatchDetailsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BettingController;
use App\Http\Controllers\TimeTestingController;
use App\Http\Controllers\Auth\RegisterController;

// Authentication routes (registration is disabled for public access)
Auth::routes(['register' => false]);

// Apply 'auth' middleware to all other routes
Route::group(['middleware' => 'auth'], function () {
    // Welcome page / Dashboard
    Route::get('/', [HomeController::class, 'showWelcomeDashboard'])->name('welcome');

    // Primary view for matches overview
    Route::view('/matches', 'matches')->name('matches.index');

    // API endpoint for matches (if still used by frontend components)
    Route::get('/api/matches', [HomeController::class, 'index'])->name('home');

    // Detail view for individual matches
    Route::get('/match-details/{matchId}', [MatchDetailsController::class, 'show'])->name('match.details');

    // Profile viewing and updating
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
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
        
        // Route to handle the manual bet settlement
        Route::post('/settle-bets', [TimeTestingController::class, 'settleBets'])->name('bets.settle');
        Route::post('/pool/update', [TimeTestingController::class, 'updatePoolSize'])->name('pool.update');

        // Admin-only User Registration Routes
        Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
        Route::post('/register', [RegisterController::class, 'register']);
    });
});