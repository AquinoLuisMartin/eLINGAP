<?php

use App\Enums\UserRole;
use App\Http\Controllers\Administration\UserController;
use App\Http\Controllers\Administration\UserPasswordController;
use App\Http\Controllers\Administration\UserStatusController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // OSCA Staff side (existing placeholder views; no new UI).
    Route::middleware('role:'.UserRole::OscaStaff->value)->group(function () {
        Route::view('dashboard', 'dashboard.index')->name('dashboard');
        Route::view('applications/verify', 'applications.verify')->name('applications.verify');
    });

    // Admin side.
    Route::middleware('role:'.UserRole::Admin->value)->prefix('administration')->name('administration.')->group(function () {
        Route::view('dashboard', 'administration.dashboard')->name('dashboard');

        Route::resource('users', UserController::class)->except(['show', 'destroy']);
        Route::patch('users/{user}/status', [UserStatusController::class, 'update'])->name('users.status.update');
        Route::patch('users/{user}/password', [UserPasswordController::class, 'update'])->name('users.password.update');
    });
});
