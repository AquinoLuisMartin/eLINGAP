<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\SystemConfiguration;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PublicContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_updates_are_reflected_without_template_changes(): void
    {
        $program = Program::create(['name' => 'Synthetic original program', 'agency' => 'Synthetic office', 'status' => 'ACTIVE']);
        $setting = SystemConfiguration::forceCreate(['key' => 'office_hours', 'value' => 'Synthetic original hours']);
        $this->get('/')->assertSee('Synthetic original program')->assertSee('Synthetic original hours');
        $program->update(['name' => 'Synthetic revised program']);
        $setting->forceFill(['value' => 'Synthetic revised hours'])->save();

        $this->get('/')->assertSee('Synthetic revised program')->assertSee('Synthetic revised hours')
            ->assertDontSee('Synthetic original program')->assertDontSee('Synthetic original hours');
    }

    #[TestWith(['/'])]
    #[TestWith(['/login'])]
    public function test_public_pages_show_supplied_office_details_without_demo_programs(string $path): void
    {
        $this->get($path)->assertOk()
            ->assertSee('No programs are currently published.')
            ->assertSee('Santa Maria Municipal Hall, J.C. De Jesus St.')
            ->assertSee('+63 44 288 2604')->assertSee('8:00 AM – 5:00 PM')
            ->assertSee('Saturday &amp; Sunday', false)->assertSee('Closed');
    }

    #[TestWith(['/'])]
    #[TestWith(['/login'])]
    public function test_public_pages_use_existing_active_and_upcoming_programs_and_allowlisted_office_information(string $path): void
    {
        Program::create(['name' => 'Synthetic active program', 'agency' => 'Synthetic office', 'description' => 'Synthetic public description', 'default_amount' => 1250, 'budget' => 98765432.10, 'status' => 'ACTIVE']);
        Program::create(['name' => 'Synthetic upcoming program', 'agency' => 'Synthetic office', 'status' => 'UPCOMING']);
        Program::create(['name' => 'Synthetic unpublished program', 'agency' => 'Synthetic office', 'status' => 'INACTIVE']);
        Program::create(['name' => 'PRIVATE-SAMPLE-PROGRAM', 'agency' => 'Synthetic office', 'status' => 'ACTIVE', 'benefit_code' => 'synthetic-registry-2000-2026']);
        foreach (['office_name' => 'Synthetic office name', 'support_email' => 'office@example.test', 'support_phone' => 'Synthetic office contact', 'office_address' => 'Synthetic office address', 'office_hours' => 'Synthetic office hours', 'internal_setting' => 'PRIVATE-SETTING-MARKER'] as $key => $value) {
            SystemConfiguration::forceCreate(['key' => $key, 'value' => $value]);
        }

        $response = $this->get($path)->assertOk()->assertSee('Synthetic active program')
            ->assertDontSee('Synthetic public description')->assertDontSee('1,250.00')
            ->assertSee('office@example.test')->assertSee('Synthetic office contact')
            ->assertSee('Synthetic office address')->assertSee('Synthetic office hours')
            ->assertSee('Synthetic upcoming program')
            ->assertDontSee('Synthetic unpublished program')->assertDontSee('PRIVATE-SETTING-MARKER')->assertDontSee('PRIVATE-SAMPLE-PROGRAM')
            ->assertDontSee('98,765,432.10')->assertDontSee('98765432.10');

        if ($path === '/') {
            $this->assertSame(['id', 'name'], array_keys($response->viewData('publicPrograms')->first()->getAttributes()));
        }
    }

    public function test_public_values_are_escaped(): void
    {
        $unsafe = '<script>alert("synthetic")</script>';
        Program::create(['name' => $unsafe, 'agency' => 'Synthetic office', 'description' => $unsafe, 'status' => 'ACTIVE']);
        SystemConfiguration::forceCreate(['key' => 'support_phone', 'value' => $unsafe]);

        $this->get('/')->assertOk()->assertSee($unsafe)->assertDontSee($unsafe, false);
    }

    public function test_program_directory_does_not_display_amounts(): void
    {
        Program::create(['name' => 'Synthetic assistance', 'agency' => 'Synthetic office', 'default_amount' => 1250, 'status' => 'ACTIVE']);

        $this->get('/')->assertOk()->assertSee('Synthetic assistance')->assertDontSee('1,250.00');
    }

    #[TestWith(['/'])]
    #[TestWith(['/login'])]
    public function test_missing_program_tables_do_not_break_the_public_or_login_page(string $path): void
    {
        DB::statement('ALTER TABLE programs RENAME TO temporarily_unavailable_programs');
        Log::shouldReceive('warning')->once()->with('Public content is unavailable.', ['failure_type' => QueryException::class]);

        $this->get($path)->assertOk()->assertSee('No programs are currently published.');
    }

    public function test_landing_navigation_removes_services_and_contact_sections(): void
    {
        $this->get('/')->assertOk()->assertSee('Programs and welfare benefits')->assertSee('Visit the office')
            ->assertDontSee('href="#services"', false)->assertDontSee('id="services"', false)
            ->assertDontSee('>Contact</a>', false)->assertDontSee('OSCA services')
            ->assertDontSee('For the seniors of Santa Maria')->assertDontSee('Explore programs')->assertDontSee('Plan your visit')
            ->assertDontSee('&rarr;', false)->assertDontSee('&nearr;', false)->assertDontSee('&darr;', false)
            ->assertSee('aria-label="Program directory"', false)
            ->assertSee('rel="noopener noreferrer"', false);
    }

    public function test_program_directory_displays_names_without_program_details(): void
    {
        Program::create([
            'name' => 'Synthetic milestone assistance', 'agency' => 'Synthetic office', 'status' => 'ACTIVE',
            'government_level' => 'NATIONAL', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31',
            'provisional' => true, 'budget' => 98765432.10,
            'benefit_rules' => ['cadence' => 'MILESTONE', 'milestones' => [80 => 10000, 100 => 100000],
                'claim_window_months' => 12, 'groceries' => true, 'internal_notes' => 'PRIVATE-RULE-MARKER'],
        ]);

        $this->get('/')->assertOk()->assertSee('Synthetic milestone assistance')
            ->assertDontSee('Age 80:')->assertDontSee('100,000.00')->assertDontSee('Milestone benefits')
            ->assertDontSee('Frequency')->assertDontSee('Government level')->assertDontSee('Verification cutoff')
            ->assertDontSee('Payout month')->assertDontSee('Program terms are provisional')
            ->assertDontSee('PRIVATE-RULE-MARKER')->assertDontSee('98,765,432.10');
    }

    public function test_program_directory_prioritizes_active_programs(): void
    {
        Program::create(['name' => 'A upcoming program', 'agency' => 'Synthetic office', 'status' => 'UPCOMING']);
        Program::create(['name' => 'Z active program', 'agency' => 'Synthetic office', 'status' => 'ACTIVE',
            'benefit_rules' => ['cadence' => 'ANNUAL', 'cutoff' => '06-30', 'payout_month' => 12,
                'october_conditions' => true, 'office_review' => true]]);

        $this->get('/')->assertOk()->assertSeeInOrder(['Z active program', 'A upcoming program'])
            ->assertDontSee('06-30')->assertDontSee('An office request and record verification are required.');
    }
}
