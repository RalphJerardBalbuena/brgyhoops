<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\GameController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/register', function () {
    return view('auth.register');
});

Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);

Route::get('/admin', function () {
    return view('admin.dashboard');
});

Route::get('/manager', [TournamentController::class, 'managerDashboard'])
    ->middleware('auth')
    ->name('manager.dashboard');

Route::get('/tournaments', [TournamentController::class, 'index'])
    ->middleware('auth')
    ->name('tournaments.index');

Route::post('/tournaments/{tournament}/select', [TournamentController::class, 'select'])
    ->middleware('auth')
    ->name('tournaments.select');

Route::middleware('auth')->group(function () {
    Route::get('/teams', [TeamController::class, 'index'])
        ->name('teams.index');

    Route::get('/teams/create', [TeamController::class, 'create'])
        ->name('teams.create');

    Route::post('/teams', [TeamController::class, 'store'])
        ->name('teams.store');

    Route::get('/teams/{team}/edit', [TeamController::class, 'edit'])
        ->name('teams.edit');

    Route::put('/teams/{team}', [TeamController::class, 'update'])
        ->name('teams.update');

    Route::delete('/teams/{team}', [TeamController::class, 'destroy'])
        ->name('teams.destroy');

    Route::get('/games', [GameController::class, 'index'])
        ->name('games.index');

    Route::get('/games/create', [GameController::class, 'create'])
        ->name('games.create');

    Route::post('/games', [GameController::class, 'store'])
        ->name('games.store');

    Route::get('/games/{game}/edit', [GameController::class, 'edit'])
        ->name('games.edit');

    Route::put('/games/{game}', [GameController::class, 'update'])
        ->name('games.update');

    Route::delete('/games/{game}', [GameController::class, 'destroy'])
        ->name('games.destroy');
});

Route::get('/tournament', function () {
    return view('public.dashboard');
});

Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
});