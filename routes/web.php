<?php

use App\Enums\UserRole;
use App\Http\Controllers\Administration\UserController;
use App\Http\Controllers\Administration\UserPasswordController;
use App\Http\Controllers\Administration\UserStatusController;
use App\Http\Controllers\Applications\ApplicationController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Programs\ProgramController;
use App\Http\Controllers\SeniorCitizens\SeniorCitizenController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware(['auth', 'auth.session', 'active'])->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::resource('senior-citizens', SeniorCitizenController::class)->except(['destroy']);
    Route::resource('programs', ProgramController::class)->only(['index', 'show']);
    Route::view('applications/verify', 'applications.verify')->name('applications.verify');
    Route::resource('applications', ApplicationController::class)->only(['index', 'create', 'store', 'show']);
    Route::patch('applications/{application}/status', [ApplicationController::class, 'updateStatus'])->name('applications.status.update');

    // OSCA Staff side (existing placeholder views; no new UI).
    Route::middleware('role:'.UserRole::OscaStaff->value)->group(function () {
        Route::view('dashboard', 'dashboard.index')->name('dashboard');
    });

    // Admin side.
    Route::middleware('role:'.UserRole::Admin->value)->prefix('administration')->name('administration.')->group(function () {
        Route::view('dashboard', 'administration.dashboard')->name('dashboard');

        Route::resource('users', UserController::class)->except(['show', 'destroy']);
        Route::resource('programs', ProgramController::class)->only(['create', 'store']);
        Route::delete('senior-citizens/{senior_citizen}', [SeniorCitizenController::class, 'destroy'])->name('senior-citizens.destroy');
        Route::patch('users/{user}/status', [UserStatusController::class, 'update'])->name('users.status.update');
        Route::patch('users/{user}/password', [UserPasswordController::class, 'update'])->name('users.password.update');
    });
});
