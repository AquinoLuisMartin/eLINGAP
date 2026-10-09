<?php

namespace Tests\Feature;

use Database\Seeders\UserSeeder;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ProductionSecurityTest extends TestCase
{
    #[TestWith(['/dashboard-preview'])]
    #[TestWith(['/payouts-preview'])]
    #[TestWith(['/reports-preview'])]
    #[TestWith(['/masterlist'])]
    #[TestWith(['/staff-preview'])]
    #[TestWith(['/registrations-preview'])]
    #[TestWith(['/registrations'])]
    #[TestWith(['/messaging'])]
    public function test_preview_pages_are_unavailable_in_production(string $path): void
    {
        $this->app->instance('env', 'production');

        $this->get($path)->assertNotFound();
    }

    public function test_login_responses_prevent_caching_framing_and_content_sniffing(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')->assertHeader('Referrer-Policy', 'same-origin');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_production_seeding_skips_demo_accounts_without_accessing_the_database(): void
    {
        $this->app->instance('env', 'production');
        $this->app['db']->connection()->disableQueryLog();
        $this->app['db']->connection()->enableQueryLog();

        (new UserSeeder)->run();

        $this->assertSame([], $this->app['db']->connection()->getQueryLog());
    }
}
