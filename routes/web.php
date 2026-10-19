<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\StandingController;
use App\Http\Controllers\PlayerTeamController;
use App\Http\Controllers\AdminTournamentController;
use App\Http\Controllers\AdminTeamApplicationController;
use App\Http\Controllers\ManagerOpenTournamentController;
use App\Http\Controllers\ManagerTournamentApplicationController;
use App\Http\Controllers\AdminTournamentApplicationController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin', [TournamentController::class, 'adminDashboard'])
        ->name('admin.dashboard');

    // Tournaments
    Route::get('/admin/tournaments', [AdminTournamentController::class, 'index'])
        ->name('admin.tournaments.index');

    Route::get('/admin/tournaments/create', [AdminTournamentController::class, 'create'])
        ->name('admin.tournaments.create');

    Route::post('/admin/tournaments', [AdminTournamentController::class, 'store'])
        ->name('admin.tournaments.store');

    Route::get('/admin/tournaments/{tournament}/edit', [AdminTournamentController::class, 'edit'])
        ->name('admin.tournaments.edit');

    Route::put('/admin/tournaments/{tournament}', [AdminTournamentController::class, 'update'])
        ->name('admin.tournaments.update');

    // Team applications
    Route::get('/admin/team-applications', [AdminTeamApplicationController::class, 'index'])
        ->name('admin.team-applications.index');

    Route::patch('/admin/team-applications/{team}/approve', [AdminTeamApplicationController::class, 'approve'])
        ->name('admin.team-applications.approve');

    Route::patch('/admin/team-applications/{team}/reject', [AdminTeamApplicationController::class, 'reject'])
        ->name('admin.team-applications.reject');

    // Manager tournament applications (separate from team roster applications)
    Route::get('/admin/tournament-applications', [AdminTournamentApplicationController::class, 'index'])
        ->name('admin.tournament-applications.index');

    Route::patch('/admin/tournament-applications/{application}/approve', [AdminTournamentApplicationController::class, 'approve'])
        ->name('admin.tournament-applications.approve');

    Route::patch('/admin/tournament-applications/{application}/reject', [AdminTournamentApplicationController::class, 'reject'])
        ->name('admin.tournament-applications.reject');
});

/*
|--------------------------------------------------------------------------
| Tournament Selection (Team Managers & Admins)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:team_manager,admin'])->group(function () {
    Route::get('/tournaments', [TournamentController::class, 'index'])
        ->name('tournaments.index');

    Route::post('/tournaments/{tournament}/select', [TournamentController::class, 'select'])
        ->name('tournaments.select');

    // Schedule & standings (read-only for managers)
    Route::get('/games', [GameController::class, 'index'])
        ->name('games.index');

    Route::get('/standings', [StandingController::class, 'index'])
        ->name('standings.index');
});

/*
|--------------------------------------------------------------------------
| Game Management (Admins only)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])->group(function () {
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

    // Official elimination (releases the manager from the overlap rule)
    Route::patch('/standings/teams/{team}/eliminate', [StandingController::class, 'eliminate'])
        ->name('standings.teams.eliminate');
});

/*
|--------------------------------------------------------------------------
| Team Manager Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:team_manager'])->group(function () {
    Route::get('/manager', [TournamentController::class, 'managerDashboard'])
        ->name('manager.dashboard');

    // Open tournaments (read-only; browsing grants no access)
    Route::get('/manager/open-tournaments', [ManagerOpenTournamentController::class, 'index'])
        ->name('manager.open-tournaments.index');

    Route::get('/manager/open-tournaments/{tournament}', [ManagerOpenTournamentController::class, 'show'])
        ->name('manager.open-tournaments.show');

    // Applying creates a pending application; access follows only from admin approval.
    Route::post('/manager/open-tournaments/{tournament}/apply', [ManagerTournamentApplicationController::class, 'store'])
        ->name('manager.open-tournaments.apply');

    // Teams
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

    Route::patch('/teams/{team}/submit', [TeamController::class, 'submit'])
        ->name('teams.submit');

    // Player join requests
    Route::get('/manager/player-requests', [PlayerTeamController::class, 'requests'])
        ->name('manager.players.requests');

    Route::patch('/manager/teams/{team}/players/{playerId}/approve', [PlayerTeamController::class, 'approve'])
        ->whereNumber('playerId')
        ->name('manager.players.approve');

    Route::patch('/manager/teams/{team}/players/{playerId}/reject', [PlayerTeamController::class, 'reject'])
        ->whereNumber('playerId')
        ->name('manager.players.reject');
});

/*
|--------------------------------------------------------------------------
| Player Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:player'])->group(function () {
    Route::get('/player/teams', [PlayerTeamController::class, 'index'])
        ->name('player.teams.index');

    Route::post('/player/teams/{team}/request', [PlayerTeamController::class, 'requestToJoin'])
        ->name('player.teams.request');

    Route::post('/player/tournament', [PlayerTeamController::class, 'selectTournament'])
        ->name('player.tournaments.select');

    Route::get('/player/schedule', [PlayerTeamController::class, 'schedule'])
        ->name('player.schedule');
});

/*
|--------------------------------------------------------------------------
| Public Tournament Dashboard
|--------------------------------------------------------------------------
*/

Route::get('/tournament', [TournamentController::class, 'publicDashboard'])
    ->name('public.dashboard');

Route::get('/tournament/{tournament}', [TournamentController::class, 'publicTournamentDashboard'])
    ->name('public.tournament');

/*
|--------------------------------------------------------------------------
| Other Pages
|--------------------------------------------------------------------------
*/

Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->name('password.request');