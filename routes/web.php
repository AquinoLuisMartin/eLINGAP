<?php

use App\Enums\UserRole;
use App\Http\Controllers\Administration\UserController;
use App\Http\Controllers\Administration\UserPasswordController;
use App\Http\Controllers\Administration\UserStatusController;
use App\Http\Controllers\Applications\ApplicationController;
use App\Http\Controllers\Programs\BeneficiaryController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Payouts\PayoutController;
use App\Http\Controllers\Payouts\PayoutScheduleController;
use App\Http\Controllers\Programs\ProgramController;
use App\Http\Controllers\Reports\ApplicationReportController;
use App\Http\Controllers\Reports\DemographicsReportController;
use App\Http\Controllers\Reports\PayoutReportController;
use App\Http\Controllers\Reports\ReportController;
use App\Http\Controllers\SeniorCitizens\SeniorCitizenController;
use App\Http\Controllers\Sms\SmsBlastController;
use App\Http\Controllers\Sms\SmsMessageController;
use App\Http\Controllers\Sms\SmsTemplateController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    Route::get('forgot-password', [PasswordResetController::class, 'createRequest'])->name('password.request');
    Route::post('forgot-password', [PasswordResetController::class, 'sendRequest'])->name('password.email');
    Route::get('reset-password/{token}', [PasswordResetController::class, 'createReset'])->name('password.reset');
    Route::post('reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});

Route::middleware(['auth', 'auth.session', 'active'])->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::resource('senior-citizens', SeniorCitizenController::class)->except(['destroy']);
    Route::resource('programs', ProgramController::class)->only(['index', 'show']);
    Route::view('applications/verify', 'applications.verify')->name('applications.verify');
    Route::resource('applications', ApplicationController::class)->only(['index', 'create', 'store', 'show']);
    Route::patch('applications/{application}/status', [ApplicationController::class, 'updateStatus'])->name('applications.status.update');
    Route::get('programs/{program}/beneficiaries', [BeneficiaryController::class, 'index'])->name('programs.beneficiaries.index');
    Route::post('programs/{program}/beneficiaries', [BeneficiaryController::class, 'store'])->name('programs.beneficiaries.store');
    Route::get('payouts', [PayoutController::class, 'index'])->name('payouts.index');
    Route::get('payouts/{payout}', [PayoutController::class, 'show'])->name('payouts.show');
    Route::patch('payouts/{payout}/status', [PayoutController::class, 'updateStatus'])->name('payouts.status.update');
    Route::get('payout-schedules', [PayoutScheduleController::class, 'index'])->name('payout-schedules.index');
    Route::get('payout-schedules/create', [PayoutScheduleController::class, 'create'])->name('payout-schedules.create');
    Route::post('payout-schedules', [PayoutScheduleController::class, 'store'])->name('payout-schedules.store');
    Route::get('payout-schedules/{payoutSchedule}', [PayoutScheduleController::class, 'show'])->name('payout-schedules.show');
    Route::get('sms/templates', [SmsTemplateController::class, 'index'])->name('sms.templates.index');
    Route::get('sms/templates/create', [SmsTemplateController::class, 'create'])->name('sms.templates.create');
    Route::post('sms/templates', [SmsTemplateController::class, 'store'])->name('sms.templates.store');
    Route::get('sms/messages', [SmsMessageController::class, 'index'])->name('sms.messages.index');
    Route::get('sms/messages/create', [SmsMessageController::class, 'create'])->name('sms.messages.create');
    Route::post('sms/messages', [SmsMessageController::class, 'store'])->name('sms.messages.store');
    Route::get('sms/blasts', fn () => view('sms.blasts.index'))->name('sms.blasts.index');
    Route::get('sms/blasts/create', [SmsBlastController::class, 'create'])->name('sms.blasts.create');
    Route::post('sms/blasts', [SmsBlastController::class, 'store'])->name('sms.blasts.store');
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/applications', ApplicationReportController::class)->name('reports.applications');
    Route::get('reports/demographics', DemographicsReportController::class)->name('reports.demographics');
    Route::get('reports/payouts', PayoutReportController::class)->name('reports.payouts');

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
