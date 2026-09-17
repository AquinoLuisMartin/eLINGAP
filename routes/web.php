<?php

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

    Route::view('dashboard', 'dashboard.index')->name('dashboard');

    Route::get('/administration/dashboard', function () {
        return view('administration.dashboard');
    });

    Route::get('/applications/verify', function () {
        return view('applications.verify');
    });

    Route::prefix('administration')->name('administration.')->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
        Route::patch('users/{user}/status', [UserStatusController::class, 'update'])->name('users.status.update');
        Route::patch('users/{user}/password', [UserPasswordController::class, 'update'])->name('users.password.update');
    });
});
