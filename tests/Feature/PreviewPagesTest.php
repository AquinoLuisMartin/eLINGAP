<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PreviewPagesTest extends TestCase
{
    #[TestWith(['/dashboard-preview', 'dashboard'])]
    #[TestWith(['/payouts-preview', 'payouts.index'])]
    #[TestWith(['/reports-preview', 'reports.index'])]
    #[TestWith(['/masterlist', 'senior-citizens.index'])]
    #[TestWith(['/staff-preview', 'dashboard'])]
    #[TestWith(['/registrations-preview', 'registrations.index'])]
    #[TestWith(['/messaging', 'sms.blasts.index'])]
    public function test_legacy_previews_redirect_to_working_pages(string $path, string $route): void
    {
        $this->get($path)->assertRedirect(route($route));
        $this->get(route($route))->assertRedirect(route('login'));
    }

    #[TestWith(['/payouts'])]
    #[TestWith(['/reports'])]
    #[TestWith(['/registrations'])]
    public function test_working_pages_require_authentication(string $path): void
    {
        $this->get($path)->assertRedirect(route('login'));
    }
}
