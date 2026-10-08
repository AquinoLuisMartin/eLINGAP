<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PreviewPagesTest extends TestCase
{
    #[TestWith(['/dashboard-preview', 'dashboard.preview'])]
    #[TestWith(['/payouts-preview', 'payouts.preview'])]
    #[TestWith(['/reports-preview', 'reports.preview'])]
    #[TestWith(['/masterlist', 'masterlist.index'])]
    #[TestWith(['/registrations', 'registrations.index'])]
    #[TestWith(['/messaging', 'messaging'])]
    public function test_preview_pages_render_without_authentication(string $path, string $view): void
    {
        $this->get($path)->assertOk()->assertViewIs($view);
    }

    #[TestWith(['/payouts'])]
    #[TestWith(['/reports'])]
    public function test_working_pages_require_authentication(string $path): void
    {
        $this->get($path)->assertRedirect(route('login'));
    }
}
