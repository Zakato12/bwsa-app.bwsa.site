<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageController;

// LOGIN ROUTES
Route::get('/', [PageController::class,'showLogin']);
Route::post('/login', [AuthController::class, 'login'])->name('login');

// LOGOUT
Route::post('/logout', [AuthController::class,'logout'])->name('logout');

// DASHBOARD
Route::get('/dashboard', [PageController::class,'main']);
Route::post('/dashboard', [AuthController::class,'changePass']);

Route::post('/change-password', [AuthController::class, 'changePass']);

