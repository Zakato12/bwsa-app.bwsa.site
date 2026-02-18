<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BarangayController;
use App\Http\Controllers\ResidentController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ReportController;

// LOGIN ROUTES
Route::get('/', [PageController::class,'showLogin']);
Route::get('/login', [PageController::class,'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// LOGOUT
Route::post('/logout', [AuthController::class,'logout'])->name('logout');

Route::middleware(['session.auth', 'session.timeout'])->group(function () {
    // DASHBOARD
    Route::get('/dashboard', [PageController::class,'main'])->name('dashboard');
    Route::post('/dashboard', [AuthController::class,'changePass']);
    Route::post('/change-password', [AuthController::class, 'changePass']) -> name('change-password');

    // USER MANAGEMENT
    Route::get('/users/add', [PageController::class,'showAddUser'])->middleware('role:admin,official');
    Route::post('/users/add', [AuthController::class, 'addUser'])->name('users.add')->middleware('role:admin,official');
    Route::get('/users/list', [UserController::class, 'listUsers'])->name('users.list')->middleware('role:admin');
    Route::put('/users/{id}', [UserController::class, 'updateUser'])->name('users.update')->middleware('role:admin');
    Route::delete('/users/{id}', [UserController::class, 'deleteUser'])->name('users.delete')->middleware('role:admin');

    // BARANGAY MANAGEMENT
    Route::resource('barangays', BarangayController::class)->middleware('role:admin');

    // RESIDENT MANAGEMENT
    Route::resource('residents', ResidentController::class)->middleware('role:official');

    // PAYMENT MANAGEMENT
    Route::get('/payments/create', [App\Http\Controllers\PaymentController::class, 'create'])->name('payments.create')->middleware('role:resident');
    Route::post('/payments', [App\Http\Controllers\PaymentController::class, 'store'])->name('payments.store')->middleware('role:resident');
    Route::get('/payments', [App\Http\Controllers\PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments/{id}/verify', [App\Http\Controllers\PaymentController::class, 'verify'])->name('payments.verify')->middleware('role:treasurer');
    Route::post('/payments/{id}/approve', [App\Http\Controllers\PaymentController::class, 'approve'])->name('payments.approve')->middleware('role:treasurer');
    Route::get('/payments/{id}/receipt', [App\Http\Controllers\PaymentController::class, 'receipt'])->name('payments.receipt');
    Route::get('/payments/bill/create', [App\Http\Controllers\PaymentController::class, 'createBill'])->name('payments.createBill')->middleware('role:treasurer');
    Route::post('/payments/bill', [App\Http\Controllers\PaymentController::class, 'storeBill'])->name('payments.storeBill')->middleware('role:treasurer');
    Route::get('/payments/walkin/create', [App\Http\Controllers\PaymentController::class, 'createWalkIn'])->name('payments.walkin.create')->middleware('role:treasurer');
    Route::post('/payments/walkin', [App\Http\Controllers\PaymentController::class, 'storeWalkIn'])->name('payments.walkin.store')->middleware('role:treasurer');

    // AUDIT LOGS
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit.logs')->middleware('role:admin');
    Route::post('/audit-logs/test', [AuditLogController::class, 'test'])->name('audit.logs.test')->middleware('role:admin');

    // REPORTS
    Route::get('/reports/residents', [ReportController::class, 'residents'])->name('reports.residents')->middleware('role:official');
    Route::get('/reports/payments', [ReportController::class, 'payments'])->name('reports.payments')->middleware('role:treasurer');
    Route::get('/reports/billing-history', [ReportController::class, 'billingHistory'])->name('reports.billing_history')->middleware('role:official,treasurer');
});
