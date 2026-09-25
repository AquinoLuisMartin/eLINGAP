<?php

namespace Tests\Feature\Programs;

use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
