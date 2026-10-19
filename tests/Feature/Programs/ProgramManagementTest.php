<?php

namespace Tests\Feature\Programs;

use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use RuntimeException;
use Tests\TestCase;

class ProgramManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_create_a_program(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('administration.programs.store'), [
            'name' => 'Centenarian Gift Award',
            'agency' => 'MSWDO',
            'budget' => 500000,
            'starts_on' => '2026-10-01',
            'ends_on' => '2026-12-31',
            'description' => 'Financial assistance for centenarians.',
        ]);

        $program = Program::firstOrFail();
        $response->assertRedirect(route('programs.show', $program));
        $this->assertSame('Centenarian Gift Award', $program->name);
        $this->assertSame('UPCOMING', $program->status);
        $this->assertSame($admin->id, $program->created_by);
        $this->assertDatabaseHas('audit_logs', ['action' => 'program.created', 'auditable_id' => $program->id, 'user_id' => $admin->id]);
    }

    public function test_program_creation_rolls_back_when_its_crucial_audit_cannot_be_saved(): void
    {
        Event::listen('eloquent.creating: '.AuditLog::class, fn () => throw new RuntimeException('Synthetic audit failure'));
        Exceptions::fake([RuntimeException::class]);

        $this->actingAs(User::factory()->admin()->create())->post(route('administration.programs.store'), [
            'name' => 'Synthetic program', 'agency' => 'Synthetic agency', 'budget' => 1000,
        ])->assertServerError();

        $this->assertDatabaseCount('programs', 0);
        $this->assertDatabaseCount('audit_logs', 0);
        Exceptions::assertReported(RuntimeException::class);
    }

    public function test_staff_cannot_create_a_program(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)->post(route('administration.programs.store'), [
            'name' => 'Unauthorized Program',
            'agency' => 'MSWDO',
            'budget' => 10000,
        ])->assertForbidden();

        $this->assertSame(0, Program::count());
    }

    public function test_program_creation_requires_valid_input(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('administration.programs.store'), [
            'name' => '',
            'agency' => '',
            'budget' => -100,
            'starts_on' => '2026-12-31',
            'ends_on' => '2026-01-01',
        ])->assertInvalid(['name', 'agency', 'budget', 'ends_on']);

        $this->assertSame(0, Program::count());
    }

    public function test_authenticated_users_can_list_and_view_programs(): void
    {
        $staff = User::factory()->create();
        $program = Program::create([
            'name' => 'Social Pension',
            'agency' => 'DSWD',
            'budget' => 1000000,
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($staff)->get(route('programs.index'))
            ->assertOk()
            ->assertSee('Social Pension');

        $this->actingAs($staff)->get(route('programs.show', $program))
            ->assertOk()
            ->assertSee('Social Pension')
            ->assertSee('Applications: 0');
    }

    public function test_staff_can_enroll_a_verified_senior_with_a_generated_id_once(): void
    {
        $staff = User::factory()->create();
        $program = Program::create(['name' => 'Assistance', 'agency' => 'OSCA', 'budget' => 10000, 'status' => 'ACTIVE']);
        $barangay = Barangay::create(['name' => 'Poblacion', 'code' => 'POB']);
        $senior = SeniorCitizen::create(['barangay_id' => $barangay->id, 'registration_number' => 'SC-TEST-001', 'first_name' => 'Maria', 'last_name' => 'Santos', 'birth_date' => '1948-01-01', 'sex' => 'FEMALE', 'address' => 'Test address', 'status' => 'VERIFIED']);
        $senior->forceFill(['verified_at' => now(), 'id_generated_at' => now(), 'osca_id_number' => 'SYN-ISSUED'])->save();
        $payload = ['program_id' => $program->id, 'senior_citizen_id' => $senior->id, 'enrolled_on' => today()->toDateString()];

        $this->actingAs($staff)->post(route('programs.beneficiaries.store', $program), $payload)->assertRedirect();
        $this->assertDatabaseHas('beneficiaries', ['program_id' => $program->id, 'senior_citizen_id' => $senior->id]);
        $this->post(route('programs.beneficiaries.store', $program), $payload)->assertSessionHasErrors('senior_citizen_id');
        $this->assertDatabaseCount('beneficiaries', 1);
    }
}
