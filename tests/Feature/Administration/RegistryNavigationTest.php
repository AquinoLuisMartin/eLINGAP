<?php

namespace Tests\Feature\Administration;

use App\Models\Barangay;
use App\Models\SeniorCitizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class RegistryNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_details_opens_the_selected_admin_profile_without_redirecting(): void
    {
        $senior = $this->senior();
        $this->actingAs(User::factory()->admin()->create());
        $url = route('administration.senior-citizens.show', $senior);

        $this->get(route('administration.dashboard', ['tab' => 'records']))->assertOk()->assertSeeHtml('href="'.$url.'"');
        $profile = $this->get($url)->assertOk()->assertViewIs('senior-citizens.show')
            ->assertSee('Synthetic Selected')->assertSee('Back to registry')
            ->assertSeeHtml('href="'.route('administration.dashboard', ['tab' => 'records']).'"');
        $this->assertTrue($profile->viewData('seniorCitizen')->is($senior));
        $this->assertActiveNavigation($profile->getContent(), 'records');
    }

    public function test_admin_profile_route_requires_authentication_and_admin_access(): void
    {
        $this->get(route('administration.senior-citizens.show', 999999))->assertRedirectToRoute('login');
        $this->actingAs(User::factory()->create())->get(route('administration.senior-citizens.show', $this->senior()))->assertForbidden();
        $this->flushSession();
        $this->actingAs(User::factory()->admin()->create())->get(route('administration.senior-citizens.show', 999999))->assertNotFound();
    }

    #[TestWith(['administration.dashboard', 'dashboard'])]
    #[TestWith(['administration.dashboard', 'records'])]
    #[TestWith(['administration.dashboard', 'programs'])]
    #[TestWith(['administration.dashboard', 'users'])]
    #[TestWith(['administration.dashboard', 'system-logs'])]
    #[TestWith(['administration.dashboard', 'configuration'])]
    #[TestWith(['administration.dashboard', 'help'])]
    #[TestWith(['administration.users.index', 'users'])]
    #[TestWith(['administration.users.create', 'users'])]
    #[TestWith(['administration.password-reset-requests.index', 'users'])]
    #[TestWith(['benefits.programs', 'programs'])]
    #[TestWith(['registrations.index', 'records'])]
    public function test_admin_pages_share_navigation_assets_and_active_states(string $route, string $active): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $response = $this->get(route($route, $route === 'administration.dashboard' ? ['tab' => $active] : []))->assertOk();

        $this->assertActiveNavigation($response->getContent(), $active);
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertSame(2, $xpath->query('//head/link[@rel="stylesheet"]')->length);
        $this->assertSame(0, $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " staff-shell ")]')->length);
        $sidebar = $xpath->query('//aside[@id="admin-navigation"]')->item(0);
        $this->assertNotNull($sidebar);
        $this->assertStringContainsString('@mouseenter=', $response->getContent());
        $this->assertStringContainsString('@mouseleave=', $response->getContent());
    }

    private function assertActiveNavigation(string $html, string $active): void
    {
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $links = (new \DOMXPath($document))->query('//aside[@id="admin-navigation"]//a[@aria-current="page"]');
        $this->assertSame(1, $links->length);
        $this->assertSame(route('administration.dashboard', ['tab' => $active]), $links->item(0)->getAttribute('href'));
    }

    private function senior(): SeniorCitizen
    {
        $area = Barangay::create(['name' => 'Synthetic area', 'code' => 'SYN']);

        return SeniorCitizen::create(['barangay_id' => $area->id, 'first_name' => 'Synthetic', 'last_name' => 'Selected', 'birth_date' => '1950-01-01', 'sex' => 'FEMALE', 'address' => 'Synthetic address']);
    }
}
