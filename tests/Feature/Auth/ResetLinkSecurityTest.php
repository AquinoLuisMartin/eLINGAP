<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class ResetLinkSecurityTest extends TestCase
{
    public function test_legacy_token_urls_are_disabled_and_recovery_secrets_are_not_in_links(): void
    {
        $this->get('/reset-password/synthetic-token')->assertNotFound();
        $this->get(route('password.reset', ['token' => 'synthetic-token', 'email' => 'synthetic@example.test']))
            ->assertOk()->assertDontSee('synthetic-token')->assertDontSee('synthetic@example.test')
            ->assertSee('name="recovery_code"', false);
    }
}
