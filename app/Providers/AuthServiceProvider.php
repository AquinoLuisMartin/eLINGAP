<?php

namespace App\Providers;

use App\Models\Application;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Policies\ApplicationPolicy;
use App\Policies\ProgramPolicy;
use App\Policies\SeniorCitizenPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        SeniorCitizen::class => SeniorCitizenPolicy::class,
        Program::class => ProgramPolicy::class,
        Application::class => ApplicationPolicy::class,
    ];
}
