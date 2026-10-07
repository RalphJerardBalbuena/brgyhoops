<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TournamentController;

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

Route::get('/tournament', function () {
    return view('public.dashboard');
});

Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
});