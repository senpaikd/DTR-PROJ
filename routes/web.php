<?php

use App\Http\Controllers\Admin\AttendanceReportExportController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\FirstLoginPasswordController;
use App\Http\Controllers\Employee\AttendanceActionController;
use App\Http\Controllers\Employee\AttendanceHistoryController;
use App\Http\Controllers\Employee\CorrectionRequestController;
use App\Http\Controllers\Employee\DashboardController;
use App\Http\Controllers\Employee\ProfileController;
use App\Http\Controllers\Employee\ProfilePasswordController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'active', 'administrator'])
    ->get('/admin/reports/attendance/export/{format}', AttendanceReportExportController::class)
    ->whereIn('format', ['csv', 'xlsx'])
    ->name('admin.reports.export');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/first-login/password', [FirstLoginPasswordController::class, 'edit'])->name('first-login.password.edit');
    Route::put('/first-login/password', [FirstLoginPasswordController::class, 'update'])->name('first-login.password.update');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::middleware('password.changed')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::post('/attendance/clock-in', [AttendanceActionController::class, 'clockIn'])->middleware('throttle:10,1')->name('attendance.clock-in');
        Route::post('/attendance/clock-out', [AttendanceActionController::class, 'clockOut'])->middleware('throttle:10,1')->name('attendance.clock-out');
        Route::get('/attendance', [AttendanceHistoryController::class, 'index'])->name('attendance.index');
        Route::get('/attendance/print', [AttendanceHistoryController::class, 'print'])->name('attendance.print');
        Route::get('/corrections', [CorrectionRequestController::class, 'index'])->name('corrections.index');
        Route::get('/corrections/create', [CorrectionRequestController::class, 'create'])->name('corrections.create');
        Route::post('/corrections', [CorrectionRequestController::class, 'store'])->name('corrections.store');
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::get('/profile/password', [ProfilePasswordController::class, 'edit'])->name('profile.password.edit');
        Route::put('/profile/password', [ProfilePasswordController::class, 'update'])->name('profile.password.update');
    });
});
