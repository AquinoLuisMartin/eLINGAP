<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

trait VerifiesLogin
{
    private function postVerifiedLogin(array $data): TestResponse
    {
        config([
            'services.turnstile.secret_key' => 'synthetic-secret',
            'services.turnstile.hostnames' => ['elingap.app'],
            'services.turnstile.action' => 'login',
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                'success' => true,
                'hostname' => 'elingap.app',
                'action' => 'login',
            ]),
        ]);

        return $this->post(route('login'), $data + ['cf-turnstile-response' => 'synthetic-token']);
    }
}
