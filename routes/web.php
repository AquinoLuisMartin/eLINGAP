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

// Frontend-only dashboard preview. The authenticated /dashboard routes remain unchanged.
Route::view('dashboard-preview', 'dashboard.index')->name('dashboard.preview');
Route::view('messaging', 'messaging')->name('messaging.preview');
Route::view('reports', 'reports.index')->name('reports.preview');
Route::view('payouts', 'payouts.index')->name('payouts.preview');

// Temporary UI preview route. Remove this route when real authentication is ready.
Route::get('masterlist', function () {
    return view('masterlist.index', [
        'user' => [
            'name' => 'Maria A.',
            'role' => 'OSCA Staff',
        ],
        'seniorCitizens' => [
            ['lastName' => 'Aquino Jr.', 'firstName' => 'Virgilio', 'middleName' => 'Navarro', 'oscaId' => 'SM-2024-0010', 'age' => 73, 'barangay' => 'Longos', 'status' => 'Confined', 'initials' => 'A', 'dob' => 'March 3, 1953', 'placeOfBirth' => 'Bocaue, Bulacan', 'sex' => 'Male', 'civilStatus' => 'Married', 'homeAddress' => 'Zone 3, Blk 8, Longos, Santa Maria, Bulacan', 'emergencyName' => 'Patricia Aquino', 'emergencyRelationship' => 'Wife', 'emergencyNumber' => '09661234567'],
            ['lastName' => 'Bautista', 'firstName' => 'Natividad', 'middleName' => 'Pascual', 'oscaId' => 'SM-2024-0005', 'age' => 85, 'barangay' => 'Poblacion', 'status' => 'Active', 'initials' => 'B', 'dob' => 'July 18, 1940', 'placeOfBirth' => 'Santa Maria, Bulacan', 'sex' => 'Female', 'civilStatus' => 'Widowed', 'homeAddress' => 'Poblacion, Santa Maria, Bulacan', 'emergencyName' => 'Rogelio Bautista', 'emergencyRelationship' => 'Son', 'emergencyNumber' => '09171234567'],
            ['lastName' => 'Cruz', 'firstName' => 'Elena', 'middleName' => 'Santos', 'oscaId' => 'SM-2024-0012', 'age' => 68, 'barangay' => 'Catmon', 'status' => 'Active', 'initials' => 'C', 'dob' => 'January 9, 1958', 'placeOfBirth' => 'Malolos, Bulacan', 'sex' => 'Female', 'civilStatus' => 'Married', 'homeAddress' => 'Catmon, Santa Maria, Bulacan', 'emergencyName' => 'Lina Cruz', 'emergencyRelationship' => 'Daughter', 'emergencyNumber' => '09281234567'],
            ['lastName' => 'Dela Cruz', 'firstName' => 'Ramon', 'middleName' => 'Flores', 'oscaId' => 'SM-2024-0008', 'age' => 77, 'barangay' => 'Guyong', 'status' => 'Relocated', 'initials' => 'D', 'dob' => 'November 21, 1948', 'placeOfBirth' => 'Santa Maria, Bulacan', 'sex' => 'Male', 'civilStatus' => 'Married', 'homeAddress' => 'Guyong, Santa Maria, Bulacan', 'emergencyName' => 'Mila Dela Cruz', 'emergencyRelationship' => 'Wife', 'emergencyNumber' => '09391234567'],
            ['lastName' => 'Garcia', 'firstName' => 'Lourdes', 'middleName' => 'Reyes', 'oscaId' => 'SM-2024-0020', 'age' => 82, 'barangay' => 'San Jose Patag', 'status' => 'Bedridden', 'initials' => 'G', 'dob' => 'May 4, 1944', 'placeOfBirth' => 'Bocaue, Bulacan', 'sex' => 'Female', 'civilStatus' => 'Married', 'homeAddress' => 'San Jose Patag, Santa Maria, Bulacan', 'emergencyName' => 'Marco Garcia', 'emergencyRelationship' => 'Son', 'emergencyNumber' => '09481234567'],
            ['lastName' => 'Mendoza', 'firstName' => 'Antonio', 'middleName' => 'Lim', 'oscaId' => 'SM-2024-0017', 'age' => 91, 'barangay' => 'Balasing', 'status' => 'Active', 'initials' => 'M', 'dob' => 'August 15, 1934', 'placeOfBirth' => 'Meycauayan, Bulacan', 'sex' => 'Male', 'civilStatus' => 'Widowed', 'homeAddress' => 'Balasing, Santa Maria, Bulacan', 'emergencyName' => 'Ana Mendoza', 'emergencyRelationship' => 'Daughter', 'emergencyNumber' => '09581234567'],
            ['lastName' => 'Navarro', 'firstName' => 'Carmen', 'middleName' => 'Aquino', 'oscaId' => 'SM-2024-0025', 'age' => 66, 'barangay' => 'Santa Clara', 'status' => 'Active', 'initials' => 'N', 'dob' => 'December 1, 1959', 'placeOfBirth' => 'Santa Maria, Bulacan', 'sex' => 'Female', 'civilStatus' => 'Married', 'homeAddress' => 'Santa Clara, Santa Maria, Bulacan', 'emergencyName' => 'Jose Navarro', 'emergencyRelationship' => 'Husband', 'emergencyNumber' => '09681234567'],
            ['lastName' => 'Reyes', 'firstName' => 'Benito', 'middleName' => 'Torres', 'oscaId' => 'SM-2024-0003', 'age' => 74, 'barangay' => 'Bulac', 'status' => 'Confined', 'initials' => 'R', 'dob' => 'February 14, 1952', 'placeOfBirth' => 'Santa Maria, Bulacan', 'sex' => 'Male', 'civilStatus' => 'Married', 'homeAddress' => 'Bulac, Santa Maria, Bulacan', 'emergencyName' => 'Rosa Reyes', 'emergencyRelationship' => 'Wife', 'emergencyNumber' => '09781234567'],
            ['lastName' => 'Santos', 'firstName' => 'Margarita', 'middleName' => 'Dizon', 'oscaId' => 'SM-2024-0015', 'age' => 87, 'barangay' => 'Cay Pombo', 'status' => 'Active', 'initials' => 'S', 'dob' => 'June 26, 1938', 'placeOfBirth' => 'San Miguel, Bulacan', 'sex' => 'Female', 'civilStatus' => 'Widowed', 'homeAddress' => 'Cay Pombo, Santa Maria, Bulacan', 'emergencyName' => 'Dario Santos', 'emergencyRelationship' => 'Son', 'emergencyNumber' => '09881234567'],
            ['lastName' => 'Villanueva', 'firstName' => 'Teodoro', 'middleName' => 'Ramos', 'oscaId' => 'SM-2024-0002', 'age' => 103, 'barangay' => 'San Vicente', 'status' => 'Deceased', 'initials' => 'V', 'dob' => 'October 10, 1922', 'placeOfBirth' => 'Santa Maria, Bulacan', 'sex' => 'Male', 'civilStatus' => 'Widowed', 'homeAddress' => 'San Vicente, Santa Maria, Bulacan', 'emergencyName' => 'Nora Villanueva', 'emergencyRelationship' => 'Daughter', 'emergencyNumber' => '09981234567'],
        ],
    ]);
})->name('masterlist.preview');

Route::redirect('staff-preview', 'masterlist')->name('staff.preview');

Route::get('registrations-preview', function () {
    return view('registrations.index', [
        'user' => ['name' => 'Maria A.', 'role' => 'OSCA Staff'],
        'applications' => [
            ['reference' => 'APP-26-1045', 'lastName' => 'Reyes', 'firstName' => 'Caridad', 'middleInitial' => 'M.', 'barangay' => 'Cay Pombo', 'submitted' => 'Oct 3, 2026', 'status' => 'Under Review', 'stage' => 'under_review', 'applicantType' => 'Proxy / Representative', 'mobile' => '09271234567', 'representative' => 'Ana Reyes', 'notes' => 'Centenarian (101). Proxy application via granddaughter.', 'badge' => 'New ID'],
            ['reference' => 'APP-26-1046', 'lastName' => 'Santos', 'firstName' => 'Margarita', 'middleInitial' => 'D.', 'barangay' => 'Santa Clara', 'submitted' => 'Oct 2, 2026', 'status' => 'Under Review', 'stage' => 'under_review', 'applicantType' => 'Walk-in (Self)', 'mobile' => '09181234567', 'representative' => '—', 'notes' => 'New registration with complete supporting documents.', 'badge' => 'New ID'],
            ['reference' => 'APP-26-1044', 'lastName' => 'Navarro', 'firstName' => 'Carmen', 'middleInitial' => 'A.', 'barangay' => 'Santa Clara', 'submitted' => 'Sep 30, 2026', 'status' => 'Approved/For Printing', 'stage' => 'approved', 'applicantType' => 'Walk-in (Self)', 'mobile' => '09191234567', 'representative' => '—', 'notes' => 'Approved and queued for physical ID printing.', 'badge' => 'New ID'],
            ['reference' => 'APP-26-1043', 'lastName' => 'Mendoza', 'firstName' => 'Antonio', 'middleInitial' => 'L.', 'barangay' => 'Balasing', 'submitted' => 'Sep 29, 2026', 'status' => 'Approved/For Printing', 'stage' => 'approved', 'applicantType' => 'Proxy / Representative', 'mobile' => '09201234567', 'representative' => 'Ana Mendoza', 'notes' => 'Documents verified by OSCA staff.', 'badge' => 'New ID'],
            ['reference' => 'APP-26-1042', 'lastName' => 'Garcia', 'firstName' => 'Lourdes', 'middleInitial' => 'R.', 'barangay' => 'San Jose Patag', 'submitted' => 'Sep 28, 2026', 'status' => 'Ready for Claiming', 'stage' => 'ready', 'applicantType' => 'Proxy / Representative', 'mobile' => '09211234567', 'representative' => 'Marco Garcia', 'notes' => 'ID is ready for claiming by authorized representative.', 'badge' => 'New ID'],
            ['reference' => 'APP-26-1047', 'lastName' => 'Gomez', 'firstName' => 'Florentino', 'middleInitial' => 'A.', 'barangay' => 'Tumana', 'submitted' => 'Sep 30, 2026', 'status' => 'Released', 'stage' => 'released', 'applicantType' => 'Walk-in (Self)', 'mobile' => '09311234567', 'representative' => '—', 'notes' => 'ID released and archived.', 'badge' => 'New ID'],
            ['reference' => 'APP-26-1041', 'lastName' => 'Dela Cruz', 'firstName' => 'Ramon', 'middleInitial' => 'F.', 'barangay' => 'Guyong', 'submitted' => 'Sep 25, 2026', 'status' => 'Released', 'stage' => 'released', 'applicantType' => 'Proxy / Representative', 'mobile' => '09411234567', 'representative' => 'Mila Dela Cruz', 'notes' => 'ID released and archived.', 'badge' => 'New ID'],
        ],
    ]);
})->name('registrations.preview');

// Frontend-only preview route. Replace with the backend route when registrations are wired.
Route::view('registrations', 'registrations.index', [
    'applications' => [],
])->name('registrations.preview.page');

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
