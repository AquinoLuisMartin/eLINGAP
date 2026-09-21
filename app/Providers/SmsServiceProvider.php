<?php

namespace App\Providers;

use App\Services\Sms\Drivers\SmsProviderDriver;
use App\Services\Sms\SmsGateway;
use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SmsGateway::class, SmsProviderDriver::class);
    }
}
