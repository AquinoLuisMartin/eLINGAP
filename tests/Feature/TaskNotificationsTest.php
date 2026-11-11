<?php

namespace Tests\Feature;

use App\Livewire\TaskNotifications;
use App\Models\Application;
use App\Models\Barangay;
use App\Models\PasswordResetRequest;
use App\Models\Program;
use App\Models\Role;
use App\Models\SeniorCitizen;
use App\Models\SmsMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class TaskNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_receive_registration_and_id_tasks_with_accessible_destinations(): void
    {
        $staff = User::factory()->create();
        $pending = $this->senior();
        $approved = $this->senior(['status' => 'VERIFIED']);
        $this->senior(['status' => 'VERIFIED', 'id_generated_at' => now()]);
        $this->senior(['status' => 'DECEASED']);
        $this->recovery($staff);

        $this->actingAs($staff);
        $component = Livewire::test(TaskNotifications::class);

        $component->assertSeeHtml('aria-label="Notifications (2)"')->assertSeeHtml('class="task-notifications-count"')
            ->assertSee('Registrations awaiting review')->assertSee('IDs awaiting preparation')
            ->assertDontSee('Password reset requests')
            ->assertSeeHtml('href="'.route('senior-citizens.show', $pending).'"')
            ->assertSeeHtml('href="'.route('senior-id.show', $approved).'"');
        $this->get(route('senior-citizens.show', $pending))->assertOk();
        $this->get(route('senior-id.show', $approved))->assertOk();
        $this->get(route('senior-citizens.index', ['status' => 'PENDING']))->assertOk();
        $this->get(route('registrations.index', ['stage' => 'VERIFIED']))->assertOk();
    }

    public function test_admin_receive_only_actionable_recovery_requests_and_no_staff_only_tasks(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();
        $pending = $this->recovery($staff);
        $this->recovery($staff, 'REJECTED');
        $this->recovery(User::factory()->inactive()->create());
        $this->recovery($admin);
        $this->senior();
        $this->senior(['status' => 'VERIFIED']);

        $this->actingAs($admin);
        $component = Livewire::test(TaskNotifications::class);

        $component->assertSee('(1)')->assertSee('Password reset requests')
            ->assertDontSee('Registrations awaiting review')->assertDontSee('IDs awaiting preparation')
            ->assertSeeHtml('href="'.route('administration.password-reset-requests.show', $pending).'"')
            ->assertSeeHtml('href="'.route('administration.password-reset-requests.index', ['status' => 'PENDING']).'"');
        $this->get(route('administration.password-reset-requests.show', $pending))->assertOk();
        $this->get(route('administration.password-reset-requests.index', ['status' => 'PENDING']))->assertOk();
    }

    #[TestWith(['OSCA_STAFF'])]
    #[TestWith(['ADMIN'])]
    public function test_both_roles_receive_pending_applications_and_failed_sms_tasks(string $role): void
    {
        $user = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => $role])->id]);
        $senior = $this->senior(['status' => 'VERIFIED', 'id_generated_at' => now()]);
        $program = Program::create(['name' => 'Synthetic assistance', 'agency' => 'OSCA', 'budget' => 1000, 'status' => 'ACTIVE']);
        $application = Application::create(['senior_citizen_id' => $senior->id, 'program_id' => $program->id,
            'application_number' => 'APP-SYNTHETIC-001', 'applied_on' => today(), 'status' => 'PENDING']);
        $this->message('FAILED');
        $this->message('SENT');
        $this->message('QUEUED');

        $this->actingAs($user);
        $component = Livewire::test(TaskNotifications::class);

        $component->assertSee('(2)')->assertSee('Benefit applications awaiting review')->assertSee('Failed SMS messages')
            ->assertSeeHtml('href="'.route('applications.show', $application).'"')
            ->assertSeeHtml('href="'.route('applications.index', ['status' => 'PENDING']).'"')
            ->assertSeeHtml('href="'.route('sms.messages.index', ['status' => 'FAILED']).'"');
        $this->get(route('applications.show', $application))->assertOk();
        $this->get(route('applications.index', ['status' => 'PENDING']))->assertOk();
        $this->get(route('sms.messages.index', ['status' => 'FAILED']))->assertViewHas('messages', fn ($messages) => $messages->total() === 1);
    }

    public function test_refresh_removes_resolved_work_and_exposes_new_work(): void
    {
        $pending = $this->senior();
        $component = Livewire::actingAs(User::factory()->create())->test(TaskNotifications::class);
        $component->assertSee('Registrations awaiting review');
        $pending->update(['status' => 'VERIFIED']);

        $component->call('$refresh');

        $component->assertDontSee('Registrations awaiting review')->assertSee('IDs awaiting preparation');
        $pending->forceFill(['id_generated_at' => now()])->save();
        $component->call('$refresh')->assertSee('No tasks currently require your attention.')
            ->assertSeeHtml('aria-label="Notifications (0)"')->assertDontSeeHtml('class="task-notifications-count"');
    }

    public function test_reviewed_recovery_requests_disappear_on_refresh(): void
    {
        $reset = $this->recovery(User::factory()->create());
        $component = Livewire::actingAs(User::factory()->admin()->create())->test(TaskNotifications::class);
        $component->assertSee('Password reset requests');
        $reset->forceFill(['status' => 'REJECTED'])->save();

        $component->call('$refresh')->assertDontSee('Password reset requests')->assertSee('(0)');
    }

    public function test_reviewed_applications_and_requeued_messages_leave_the_task_list(): void
    {
        $senior = $this->senior(['status' => 'VERIFIED', 'id_generated_at' => now()]);
        $program = Program::create(['name' => 'Synthetic assistance', 'agency' => 'OSCA', 'budget' => 1000, 'status' => 'ACTIVE']);
        $application = Application::create(['senior_citizen_id' => $senior->id, 'program_id' => $program->id,
            'application_number' => 'APP-SYNTHETIC-001', 'applied_on' => today(), 'status' => 'PENDING']);
        $message = $this->message('FAILED');
        $component = Livewire::actingAs(User::factory()->create())->test(TaskNotifications::class);
        $component->assertSee('(2)');
        $application->update(['status' => 'APPROVED']);
        $message->update(['status' => 'QUEUED']);

        $component->call('$refresh')->assertSee('(0)')->assertSee('No tasks currently require your attention.');
    }

    #[TestWith(['OSCA_STAFF', 'dashboard'])]
    #[TestWith(['ADMIN', 'administration.dashboard'])]
    #[TestWith(['ADMIN', 'administration.users.index'])]
    public function test_notifications_are_present_on_staff_and_admin_layouts(string $role, string $route): void
    {
        $user = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => $role])->id]);

        $this->actingAs($user)->get(route($route))->assertSeeLivewire(TaskNotifications::class);
    }

    public function test_staff_cannot_open_admin_recovery_tasks(): void
    {
        $reset = $this->recovery(User::factory()->create());

        $this->actingAs(User::factory()->create())->get(route('administration.password-reset-requests.show', $reset))->assertForbidden();
    }

    public function test_guests_cannot_load_notification_data(): void
    {
        Livewire::test(TaskNotifications::class)->assertForbidden();
    }

    public function test_inactive_accounts_cannot_load_notification_data(): void
    {
        Livewire::actingAs(User::factory()->inactive()->create())->test(TaskNotifications::class)->assertForbidden();
    }

    public function test_accounts_awaiting_password_change_cannot_load_notification_data(): void
    {
        Livewire::actingAs(User::factory()->create(['must_change_password' => true]))->test(TaskNotifications::class)->assertForbidden();
    }

    public function test_sms_filter_preserves_status_during_pagination_and_rejects_invalid_input(): void
    {
        $this->actingAs(User::factory()->create());
        for ($i = 0; $i < 16; $i++) {
            $this->message('FAILED');
        }
        $this->message('SENT');

        $this->get(route('sms.messages.index', ['status' => 'FAILED', 'page' => 2]))
            ->assertViewHas('messages', fn ($messages) => $messages->total() === 16 && $messages->count() === 1)
            ->assertSee('status=FAILED');
        $this->get(route('sms.messages.index', ['status' => 'invalid']))->assertSessionHasErrors(['status' => 'The selected status is invalid.']);
        $this->get(route('sms.messages.index'))->assertViewHas('messages', fn ($messages) => $messages->total() === 17);
    }

    private function senior(array $attributes = []): SeniorCitizen
    {
        $barangay = Barangay::firstOrCreate(['code' => 'SYN'], ['name' => 'Synthetic Barangay']);

        return SeniorCitizen::forceCreate(['barangay_id' => $barangay->id, 'first_name' => 'Synthetic', 'last_name' => 'Applicant',
            'birth_date' => '1960-01-01', 'sex' => 'FEMALE', 'address' => 'Synthetic address', 'status' => 'PENDING', ...$attributes]);
    }

    private function recovery(User $user, string $status = 'PENDING'): PasswordResetRequest
    {
        return PasswordResetRequest::forceCreate(['user_id' => $user->id, 'status' => $status, 'requested_at' => now()]);
    }

    private function message(string $status): SmsMessage
    {
        return SmsMessage::create(['status' => $status, 'message' => 'Synthetic delivery task', 'recipient_number' => '+639170000001', 'queued_at' => now()]);
    }
}
