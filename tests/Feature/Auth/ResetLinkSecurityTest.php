<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Tests\TestCase;

class ResetLinkSecurityTest extends TestCase
{
    public function test_reset_links_use_the_configured_host_instead_of_the_request_host(): void
    {
        config(['app.url' => 'https://elingap.example.test']);
        $user = new User(['email' => 'synthetic@example.test']);
        $this->app['url']->forceRootUrl('https://untrusted.example.test');

        $url = (new ResetPassword('synthetic-token'))->toMail($user)->actionUrl;

        $this->assertStringStartsWith('https://elingap.example.test/reset-password/', $url);
        $this->assertStringNotContainsString('untrusted.example.test', $url);
    }
}
