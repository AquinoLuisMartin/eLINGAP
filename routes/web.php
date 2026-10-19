<?php

use App\Enums\UserRole;
use App\Http\Controllers\Administration\UserController;
use App\Http\Controllers\Administration\UserPasswordController;
use App\Http\Controllers\Administration\UserStatusController;
use App\Http\Controllers\Applications\ApplicationController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Payouts\PayoutController;
use App\Http\Controllers\Payouts\PayoutScheduleController;
use App\Http\Controllers\Programs\BeneficiaryController;
use App\Http\Controllers\Programs\ProgramController;
use App\Http\Controllers\Reports\ApplicationReportController;
use App\Http\Controllers\Reports\DemographicsReportController;
use App\Http\Controllers\Reports\PayoutReportController;
use App\Http\Controllers\Reports\ReportController;
use App\Http\Controllers\Reports\StaffReportController;
use App\Http\Controllers\SeniorCitizens\PublicIdVerificationController;
use App\Http\Controllers\SeniorCitizens\SeniorCitizenController;
use App\Http\Controllers\SeniorCitizens\SeniorCitizenDeathController;
use App\Http\Controllers\SeniorCitizens\SeniorCitizenIdController;
use App\Http\Controllers\SeniorCitizens\SeniorCitizenTransferController;
use App\Http\Controllers\SeniorCitizens\SeniorImportController;
use App\Http\Controllers\Sms\SmsBlastController;
use App\Http\Controllers\Sms\SmsMessageController;
use App\Http\Controllers\Sms\SmsTemplateController;
use App\Http\Middleware\AuditActivity;
use App\Http\Middleware\EnsurePreviewIsLocal;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('verify-id/{token}', PublicIdVerificationController::class)->middleware('throttle:60,1,id-verification')->name('id.verify');

Route::middleware(EnsurePreviewIsLocal::class)->group(function () {
    foreach ([
        'dashboard-preview' => ['dashboard', 'dashboard.preview'],
        'masterlist' => ['senior-citizens.index', 'masterlist.preview'],
        'staff-preview' => ['dashboard', 'staff.preview'],
        'registrations-preview' => ['registrations.index', 'registrations.preview'],
        'reports-preview' => ['reports.index', 'reports.preview'],
        'payouts-preview' => ['payouts.index', 'payouts.preview'],
        'messaging' => ['sms.blasts.index', 'messaging.preview'],
    ] as $path => [$destination, $name]) {
        Route::get($path, fn () => redirect()->route($destination))->name($name);
    }
});

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:20,1,login');
    Route::get('forgot-password', [PasswordResetController::class, 'createRequest'])->name('password.request');
    Route::post('forgot-password', [PasswordResetController::class, 'sendRequest'])->middleware('throttle:5,1,password-recovery')->name('password.email');
    Route::get('reset-password/{token}', [PasswordResetController::class, 'createReset'])->name('password.reset');
    Route::post('reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1,password-reset')->name('password.update');
});

Route::middleware(['auth', 'auth.session', 'active', AuditActivity::class])->group(function () {
    Route::view('registrations', 'registrations.index')->name('registrations.index');
    Route::get('registrations/import', [SeniorImportController::class, 'create'])->name('registrations.import.create');
    Route::get('registrations/import/template/{format}', [SeniorImportController::class, 'template'])->name('registrations.import.template');
    Route::post('registrations/import', [SeniorImportController::class, 'preview'])->middleware('throttle:5,1,senior-import')->name('registrations.import.preview');
    Route::get('registrations/import/{token}', [SeniorImportController::class, 'show'])->whereUuid('token')->name('registrations.import.show');
    Route::post('registrations/import/{token}', [SeniorImportController::class, 'store'])->whereUuid('token')->middleware('throttle:5,1,senior-import-confirm')->name('registrations.import.store');
    Route::delete('registrations/import/{token}', [SeniorImportController::class, 'destroy'])->whereUuid('token')->name('registrations.import.destroy');
    Route::view('benefits', 'benefits.page')->name('benefits.workspace');
    Route::view('benefits/programs', 'benefits.page')->name('benefits.programs');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('senior-id/officials', [SeniorCitizenIdController::class, 'officials'])->name('senior-id.officials');
    Route::post('senior-id/batch-print', [SeniorCitizenIdController::class, 'batchPrint'])->middleware('throttle:20,1,id-print')->name('senior-id.batch-print');
    Route::get('senior-citizens/{senior_citizen}/identification', [SeniorCitizenIdController::class, 'show'])->name('senior-id.show');
    Route::get('senior-citizens/{senior_citizen}/identification/preview', [SeniorCitizenIdController::class, 'preview'])->name('senior-id.preview');
    Route::post('senior-citizens/{senior_citizen}/identification/print', [SeniorCitizenIdController::class, 'print'])->middleware('throttle:20,1,id-print')->name('senior-id.print');

    Route::resource('senior-citizens', SeniorCitizenController::class)->except(['destroy']);
    Route::post('senior-citizens/{senior_citizen}/transfers', [SeniorCitizenTransferController::class, 'store'])->name('senior-citizens.transfers.store');
    Route::post('senior-citizens/{senior_citizen}/transfers/{transfer}/certificate', [SeniorCitizenTransferController::class, 'certificate'])->middleware('throttle:20,1,cancellation-certificate')->name('senior-citizens.transfers.certificate');
    Route::post('senior-citizens/{senior_citizen}/death', [SeniorCitizenDeathController::class, 'store'])->name('senior-citizens.death.store');
    Route::patch('senior-citizens/{senior_citizen}/death', [SeniorCitizenDeathController::class, 'correct'])->name('senior-citizens.death.correct');
    Route::get('senior-citizens/{senior_citizen}/photo', [SeniorCitizenController::class, 'photo'])->name('senior-citizens.photo');
    Route::get('senior-citizens/{senior_citizen}/id-card', [SeniorCitizenController::class, 'idCard'])->name('senior-citizens.id-card');
    Route::get('senior-citizens/{senior_citizen}/documents/{document}', [SeniorCitizenController::class, 'document'])->name('senior-citizens.document');
    Route::resource('programs', ProgramController::class)->only(['index', 'show']);
    Route::view('applications/verify', 'applications.verify')->name('applications.verify');
    Route::resource('applications', ApplicationController::class)->only(['index', 'create', 'store', 'show']);
    Route::patch('applications/{application}/status', [ApplicationController::class, 'updateStatus'])->name('applications.status.update');
    Route::get('programs/{program}/beneficiaries', [BeneficiaryController::class, 'index'])->name('programs.beneficiaries.index');
    Route::post('programs/{program}/beneficiaries', [BeneficiaryController::class, 'store'])->name('programs.beneficiaries.store');
    Route::get('payouts', [PayoutController::class, 'index'])->name('payouts.index');
    Route::get('payouts/{payout}', [PayoutController::class, 'show'])->name('payouts.show');
    Route::patch('payouts/{payout}/status', [PayoutController::class, 'updateStatus'])->name('payouts.status.update');
    Route::patch('payouts/{payout}/reverse', [PayoutController::class, 'reverse'])->name('payouts.reverse');
    Route::get('payout-schedules', [PayoutScheduleController::class, 'index'])->name('payout-schedules.index');
    Route::get('payout-schedules/create', [PayoutScheduleController::class, 'create'])->name('payout-schedules.create');
    Route::post('payout-schedules', [PayoutScheduleController::class, 'store'])->name('payout-schedules.store');
    Route::get('payout-schedules/{payoutSchedule}', [PayoutScheduleController::class, 'show'])->name('payout-schedules.show');
    Route::get('sms/templates', [SmsTemplateController::class, 'index'])->name('sms.templates.index');
    Route::get('sms/templates/create', [SmsTemplateController::class, 'create'])->name('sms.templates.create');
    Route::post('sms/templates', [SmsTemplateController::class, 'store'])->name('sms.templates.store');
    Route::get('sms/messages', [SmsMessageController::class, 'index'])->name('sms.messages.index');
    Route::get('sms/messages/create', [SmsMessageController::class, 'create'])->name('sms.messages.create');
    Route::post('sms/messages', [SmsMessageController::class, 'store'])->middleware('throttle:20,1,sms-send')->name('sms.messages.store');
    Route::get('sms/blasts', [SmsBlastController::class, 'index'])->name('sms.blasts.index');
    Route::get('sms/blasts/create', [SmsBlastController::class, 'create'])->name('sms.blasts.create');
    Route::get('sms/blasts/{blast}', [SmsBlastController::class, 'show'])->name('sms.blasts.show');
    Route::post('sms/blasts', [SmsBlastController::class, 'store'])->middleware('throttle:2,1,sms-broadcast')->name('sms.blasts.store');
    Route::post('sms/messages/{message}/retry', [SmsBlastController::class, 'retry'])->middleware('throttle:20,1,sms-retry')->name('sms.messages.retry');
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/applications', ApplicationReportController::class)->name('reports.applications');
    Route::get('reports/demographics', DemographicsReportController::class)->name('reports.demographics');
    Route::get('reports/payouts', PayoutReportController::class)->name('reports.payouts');
    Route::get('reports/workspace/{type}', [StaffReportController::class, 'show'])->name('reports.workspace');
    Route::get('reports/workspace/{type}/export', [StaffReportController::class, 'export'])->middleware('throttle:3,1,report-export')->name('reports.export');

    Route::get('dashboard', DashboardController::class)
        ->middleware('role:'.UserRole::OscaStaff->value.','.UserRole::Admin->value)
        ->name('dashboard');

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
