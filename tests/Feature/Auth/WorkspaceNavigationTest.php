<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class WorkspaceNavigationTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['benefits.programs'])]
    #[TestWith(['benefits.workspace'])]
    #[TestWith(['senior-citizens.index'])]
    #[TestWith(['registrations.index'])]
    #[TestWith(['payouts.index'])]
    #[TestWith(['payout-schedules.index'])]
    #[TestWith(['sms.blasts.index'])]
    #[TestWith(['reports.index'])]
    #[TestWith(['programs.index'])]
    #[TestWith(['applications.index'])]
    #[TestWith(['administration.users.index'])]
    public function test_admin_keeps_administration_navigation_on_shared_pages(string $route): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route($route));

        $response->assertOk()
            ->assertSeeHtml('aria-label="System navigation"')
            ->assertSeeHtml('href="'.route('administration.dashboard', ['tab' => 'configuration']).'"')
            ->assertSeeText('System Administration')
            ->assertDontSeeHtml('aria-label="OSCA Staff navigation"')
            ->assertDontSeeText('OSCA Staff Portal');
    }

    public function test_staff_keeps_staff_navigation_and_programs_highlight_the_benefits_workspace(): void
    {
        $staff = User::factory()->create();

        $response = $this->actingAs($staff)->get(route('programs.index'));

        $response->assertOk()
            ->assertSeeHtml('aria-label="OSCA Staff navigation"')
            ->assertDontSeeHtml('aria-label="System navigation"')
            ->assertDontSeeText('System Configuration');

        $this->assertCurrentNavigationLink($response->getContent(), 'OSCA Staff navigation', route('benefits.workspace'));
    }

    public function test_benefit_rules_highlight_the_admin_programs_section(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('benefits.programs'));

        $this->assertCurrentNavigationLink($response->getContent(), 'System navigation', route('administration.dashboard', ['tab' => 'programs']));
    }

    private function assertCurrentNavigationLink(string $html, string $navigation, string $url): void
    {
        $document = new \DOMDocument;
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $links = (new \DOMXPath($document))->query('//nav[@aria-label="'.$navigation.'"]//a[@aria-current="page"]');

        $this->assertCount(1, $links);
        $this->assertSame($url, $links->item(0)->getAttribute('href'));
    }
}
