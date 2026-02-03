<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BarangayController;
use App\Http\Controllers\ResidentController;

// LOGIN ROUTES
Route::get('/', [PageController::class,'showLogin']);
Route::get('/login', [PageController::class,'userincactive'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// LOGOUT
Route::post('/logout', [AuthController::class,'logout'])->name('logout');

// DASHBOARD
Route::get('/dashboard', [PageController::class,'main'])->name('dashboard');
Route::post('/dashboard', [AuthController::class,'changePass']);

Route::post('/change-password', [AuthController::class, 'changePass']) -> name('change-password');

// USER MANAGEMENT
Route::get('/users/add', [PageController::class,'showAddUser']);
Route::post('/users/add', [AuthController::class, 'addUser'])->name('users.add');
Route::get('/users/list', [UserController::class, 'listUsers'])->name('users.list');

// BARANGAY MANAGEMENT
Route::resource('barangays', BarangayController::class);

// RESIDENT MANAGEMENT
Route::resource('residents', ResidentController::class);

// PAYMENT MANAGEMENT
Route::get('/payments/create', [App\Http\Controllers\PaymentController::class, 'create'])->name('payments.create');
Route::post('/payments', [App\Http\Controllers\PaymentController::class, 'store'])->name('payments.store');
Route::get('/payments', [App\Http\Controllers\PaymentController::class, 'index'])->name('payments.index');
Route::post('/payments/{id}/verify', [App\Http\Controllers\PaymentController::class, 'verify'])->name('payments.verify');
Route::post('/payments/{id}/approve', [App\Http\Controllers\PaymentController::class, 'approve'])->name('payments.approve');
Route::get('/payments/bill/create', [App\Http\Controllers\PaymentController::class, 'createBill'])->name('payments.createBill');
Route::post('/payments/bill', [App\Http\Controllers\PaymentController::class, 'storeBill'])->name('payments.storeBill');