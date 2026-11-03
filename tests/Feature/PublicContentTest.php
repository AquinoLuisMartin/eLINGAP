<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\SystemConfiguration;
use Database\Seeders\BenefitProgramSeeder;
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
        $this->seed(BenefitProgramSeeder::class);
        $program = Program::where('benefit_code', 'december-assistance')->sole();
        $program->update(['status' => 'ACTIVE']);
        $setting = SystemConfiguration::forceCreate(['key' => 'office_hours', 'value' => 'Synthetic original hours']);
        $this->get('/')->assertSee('Annual December Senior Assistance')->assertSee('Synthetic original hours');
        $program->update(['name' => 'Localized Social Pension']);
        $setting->forceFill(['value' => 'Synthetic revised hours'])->save();

        $this->get('/')->assertSee('Localized Social Pension')->assertSee('Synthetic revised hours')
            ->assertDontSee('Annual December Senior Assistance')->assertDontSee('Synthetic original hours');
    }

    #[TestWith(['/'])]
    #[TestWith(['/login'])]
    public function test_public_pages_show_supplied_office_details_without_demo_programs(string $path): void
    {
        foreach (['office_address' => 'Synthetic office address', 'support_phone' => 'Synthetic office contact', 'office_hours' => 'Synthetic working days and hours'] as $key => $value) {
            SystemConfiguration::forceCreate(['key' => $key, 'value' => $value]);
        }

        $this->get($path)->assertOk()
            ->assertSee('No programs are currently published.')
            ->assertSee('Synthetic office address')->assertDontSee('Synthetic office contact')
            ->assertSee('Synthetic working days and hours')->assertDontSee('Saturday &amp; Sunday', false);
    }

    #[TestWith(['/'])]
    #[TestWith(['/login'])]
    public function test_public_pages_use_existing_active_and_upcoming_programs_and_allowlisted_office_information(string $path): void
    {
        $this->seed(BenefitProgramSeeder::class);
        Program::where('benefit_code', 'december-assistance')->update(['description' => 'Synthetic public description', 'default_amount' => 1250, 'budget' => 98765432.10, 'status' => 'ACTIVE']);
        Program::where('benefit_code', 'lgu-age-90')->update(['status' => 'UPCOMING']);
        Program::where('benefit_code', 'tss')->update(['status' => 'INACTIVE']);
        foreach (['office_name' => 'Synthetic office name', 'support_email' => 'office@example.test', 'support_phone' => 'Synthetic office contact', 'office_address' => 'Synthetic office address', 'office_hours' => 'Synthetic office hours', 'internal_setting' => 'PRIVATE-SETTING-MARKER'] as $key => $value) {
            SystemConfiguration::forceCreate(['key' => $key, 'value' => $value]);
        }

        $response = $this->get($path)->assertOk()->assertSee('Annual December Senior Assistance')
            ->assertDontSee('Synthetic public description')->assertDontSee('1,250.00')
            ->assertSee('office@example.test')->assertDontSee('Synthetic office contact')
            ->assertSee('Synthetic office address')->assertSee('Synthetic office hours')
            ->assertSee('LGU Age 90 Cash Gift')
            ->assertDontSee('T.S.S. - Tulong sa Senior')->assertDontSee('PRIVATE-SETTING-MARKER')
            ->assertDontSee('98,765,432.10')->assertDontSee('98765432.10');

        if ($path === '/') {
            $this->assertSame(['id', 'name'], array_keys($response->viewData('publicPrograms')->first()->getAttributes()));
        }
    }

    public function test_public_values_are_escaped(): void
    {
        $unsafe = '<script>alert("synthetic")</script>';
        Program::create(['name' => $unsafe, 'agency' => 'Synthetic office', 'description' => $unsafe, 'status' => 'ACTIVE']);
        SystemConfiguration::forceCreate(['key' => 'office_hours', 'value' => $unsafe]);

        $this->get('/')->assertOk()->assertSee($unsafe)->assertDontSee($unsafe, false);
    }

    public function test_program_directory_does_not_display_amounts(): void
    {
        $this->seed(BenefitProgramSeeder::class);
        Program::where('benefit_code', 'december-assistance')->update(['default_amount' => 1250, 'status' => 'ACTIVE']);

        $this->get('/')->assertOk()->assertSee('Annual December Senior Assistance')->assertDontSee('1,250.00');
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
        SystemConfiguration::forceCreate(['key' => 'office_address', 'value' => 'Synthetic office address']);

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
        $this->seed(BenefitProgramSeeder::class);
        Program::where('benefit_code', 'national-milestones')->update([
            'status' => 'ACTIVE', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31',
            'provisional' => true, 'budget' => 98765432.10,
            'benefit_rules' => ['cadence' => 'MILESTONE', 'milestones' => [80 => 10000, 100 => 100000],
                'claim_window_months' => 12, 'groceries' => true, 'internal_notes' => 'PRIVATE-RULE-MARKER'],
        ]);

        $this->get('/')->assertOk()->assertSee('Expanded Centenarians Act (RA 11982)')
            ->assertDontSee('Age 80:')->assertDontSee('100,000.00')->assertDontSee('Milestone benefits')
            ->assertDontSee('Frequency')->assertDontSee('Government level')->assertDontSee('Verification cutoff')
            ->assertDontSee('Payout month')->assertDontSee('Program terms are provisional')
            ->assertDontSee('PRIVATE-RULE-MARKER')->assertDontSee('98,765,432.10');
    }

    public function test_program_directory_prioritizes_active_programs(): void
    {
        $this->seed(BenefitProgramSeeder::class);
        Program::where('benefit_code', 'tss')->update(['status' => 'ACTIVE',
            'benefit_rules' => ['cadence' => 'ANNUAL', 'cutoff' => '06-30', 'payout_month' => 12,
                'october_conditions' => true, 'office_review' => true]]);

        $this->get('/')->assertOk()->assertSeeInOrder(['T.S.S. - Tulong sa Senior', 'Annual December Senior Assistance'])
            ->assertDontSee('06-30')->assertDontSee('An office request and record verification are required.');
    }
}
