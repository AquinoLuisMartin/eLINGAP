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
    public function test_public_pages_have_empty_states_instead_of_demo_data(string $path): void
    {
        $this->get($path)->assertOk()
            ->assertSee('No active programs are currently published.')
            ->assertSee('Office email not configured.')
            ->assertSee('Office phone not configured.');
    }

    #[TestWith(['/'])]
    #[TestWith(['/login'])]
    public function test_public_pages_use_only_active_programs_and_allowlisted_office_information(string $path): void
    {
        Program::create(['name' => 'Synthetic active program', 'agency' => 'Synthetic office', 'description' => 'Synthetic public description', 'default_amount' => 1250, 'budget' => 98765432.10, 'status' => 'ACTIVE']);
        Program::create(['name' => 'Synthetic unpublished program', 'agency' => 'Synthetic office', 'status' => 'UPCOMING']);
        foreach (['office_name' => 'Synthetic office name', 'support_email' => 'office@example.test', 'support_phone' => 'Synthetic office contact', 'office_address' => 'Synthetic office address', 'office_hours' => 'Synthetic office hours', 'internal_setting' => 'PRIVATE-SETTING-MARKER'] as $key => $value) {
            SystemConfiguration::forceCreate(['key' => $key, 'value' => $value]);
        }

        $response = $this->get($path)->assertOk()->assertSee('Synthetic active program')
            ->assertSee('Synthetic public description')->assertSee('1,250.00')
            ->assertSee('office@example.test')->assertSee('Synthetic office contact')
            ->assertSee('Synthetic office address')->assertSee('Synthetic office hours')
            ->assertDontSee('Synthetic unpublished program')->assertDontSee('PRIVATE-SETTING-MARKER')
            ->assertDontSee('98,765,432.10')->assertDontSee('98765432.10');

        if ($path === '/') {
            $this->assertSame(['id', 'name', 'agency', 'description', 'default_amount'], array_keys($response->viewData('publicPrograms')->sole()->getAttributes()));
        }
    }

    public function test_public_values_are_escaped(): void
    {
        $unsafe = '<script>alert("synthetic")</script>';
        Program::create(['name' => $unsafe, 'agency' => 'Synthetic office', 'description' => $unsafe, 'status' => 'ACTIVE']);
        SystemConfiguration::forceCreate(['key' => 'support_phone', 'value' => $unsafe]);

        $this->get('/')->assertOk()->assertSee($unsafe)->assertDontSee($unsafe, false);
    }

    #[TestWith([null, 'Amount confirmed during assessment.'])]
    #[TestWith(['0.00', '0.00'])]
    public function test_program_amounts_do_not_invent_a_default(?string $amount, string $expected): void
    {
        Program::create(['name' => 'Synthetic assistance', 'agency' => 'Synthetic office', 'default_amount' => $amount, 'status' => 'ACTIVE']);

        $this->get('/')->assertOk()->assertSee($expected);
    }

    #[TestWith(['/'])]
    #[TestWith(['/login'])]
    public function test_missing_program_tables_do_not_break_the_public_or_login_page(string $path): void
    {
        DB::statement('ALTER TABLE programs RENAME TO temporarily_unavailable_programs');
        Log::shouldReceive('warning')->once()->with('Public content is unavailable.', ['failure_type' => QueryException::class]);

        $this->get($path)->assertOk()->assertSee('No active programs are currently published.');
    }
}
