<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return view('auth.login');
});

Route::get('/register', function () {
    return view('auth.register');
});

Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);

Route::get('/admin', function () {
    return view('admin.dashboard');
});

Route::get('/manager', function () {
    return view('manager.dashboard');
});

Route::get('/tournament', function () {
    return view('public.dashboard');
});

Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
});