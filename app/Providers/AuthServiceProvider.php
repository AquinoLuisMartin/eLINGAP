<?php

namespace App\Providers;

use App\Models\Application;
use App\Models\Beneficiary;
use App\Models\Payout;
use App\Models\PayoutSchedule;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Models\SmsMessage;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Policies\ApplicationPolicy;
use App\Policies\PayoutPolicy;
use App\Policies\ProgramPolicy;
use App\Policies\ReportPolicy;
use App\Policies\SeniorCitizenPolicy;
use App\Policies\SmsMessagePolicy;
use App\Policies\UserPolicy;
use App\Services\Reports\StaffReport;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        SeniorCitizen::class => SeniorCitizenPolicy::class,
        Program::class => ProgramPolicy::class,
        Application::class => ApplicationPolicy::class,
        Beneficiary::class => ProgramPolicy::class,
        Payout::class => PayoutPolicy::class,
        PayoutSchedule::class => PayoutPolicy::class,
        SmsMessage::class => SmsMessagePolicy::class,
        SmsTemplate::class => SmsMessagePolicy::class,
        User::class => UserPolicy::class,
        StaffReport::class => ReportPolicy::class,
    ];
}
