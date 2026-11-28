<?php

namespace Tests\Feature\Sms;

use App\Livewire\TaskNotifications;
use App\Models\Barangay;
use App\Models\SeniorCitizen;
use App\Models\SmsMessage;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Services\Sms\SmsGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class BirthdayGreetingTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_see_verified_birthdays_today_and_through_day_seven(): void
    {
        $this->travelTo(Carbon::parse('2026-12-29 09:00'));
        $today = $this->senior('Today', '1944-12-29');
        $this->senior('Boundary', '1945-01-05', ['contact_number' => null, 'representative_contact' => '+639000000002']);
        $this->senior('NewYear', '1945-01-01');
        $this->senior('OutOfRange', '1945-01-06');
        foreach (['ARCHIVED', 'DECEASED', 'PENDING', 'TRANSFERRED'] as $status) {
            $this->senior($status, '1945-12-29', ['status' => $status]);
        }

        $this->actingAs(User::factory()->create());

        Livewire::test(TaskNotifications::class)->assertSeeInOrder(['Birthdays', 'Synthetic Today', 'Turning 82', 'Today', 'Synthetic NewYear', 'In 3 days', 'Synthetic Boundary', 'In 7 days', 'No mobile number'])
            ->assertSee($today->osca_id_number)->assertSee('Synthetic Barangay')
            ->assertSeeHtml('aria-label="Notifications (4)"')
            ->assertSeeHtml('href="'.route('sms.messages.birthday', $today).'"')
            ->assertDontSee('Synthetic OutOfRange')->assertDontSee('Synthetic ARCHIVED')->assertDontSee('Synthetic DECEASED')
            ->assertDontSee('Synthetic PENDING')->assertDontSee('Synthetic TRANSFERRED');
        $this->get(route('dashboard'))->assertOk()->assertSeeHtml("Upcoming &amp; Today's Birthdays")->assertSee('Synthetic NewYear');
    }

    public function test_greeting_is_prefilled_and_queued_only_once_for_the_birthday_year(): void
    {
        $this->travelTo(Carbon::parse('2026-12-29 09:00'));
        $senior = $this->senior('NewYear', '1945-01-01');
        $staff = User::factory()->create();
        $this->actingAs($staff);
        $form = route('sms.messages.birthday', $senior);
        $send = route('sms.messages.birthday.store', $senior);
        $template = SmsTemplate::where('name', 'Birthday Greeting')->sole();
        $message = str_replace('[Senior Name]', $senior->full_name, $template->body);

        $this->get(route('sms.templates.index'))->assertOk()->assertSee('Birthday Greeting');
        $this->get(route('sms.blasts.create'))->assertOk()->assertDontSee('Birthday Greeting')
            ->assertViewHas('retiredTemplates', [(string) $template->id]);
        $this->get($form)->assertOk()->assertSee('900-000-0001')->assertSee($message)->assertSeeHtml('x-bind:disabled="sending"');
        $payload = ['birthday_year' => 2027, 'recipient_number' => '900-000-0001', 'message' => $message];
        $this->post($send, $payload)->assertRedirect(route('sms.messages.index'));
        $this->post($send, $payload)->assertConflict();
        $this->assertDatabaseCount('sms_messages', 1);
        $this->assertDatabaseCount('jobs', 1);
        $queued = SmsMessage::sole();
        $this->assertDatabaseHas('sms_messages', [
            'id' => $queued->id, 'birthday_year' => 2027, 'status' => 'QUEUED', 'sms_template_id' => $template->id,
            'senior_citizen_id' => $senior->id, 'created_by' => $staff->id,
        ]);
        Livewire::test(TaskNotifications::class)->assertSee('Greeting Queued')->assertDontSee('Send Greeting');

        $this->mock(SmsGateway::class)->expects('send')->with('+639000000001', $message, 'sms-'.$queued->id)
            ->andReturn(['provider_message_id' => 'synthetic-provider-id', 'response' => []]);
        $this->artisan('queue:work', ['connection' => 'database-atomic', '--once' => true])->assertSuccessful();

        $this->travelTo(Carbon::parse('2027-01-01 09:00'));
        Livewire::test(TaskNotifications::class)->assertSee('Greeting Sent')->assertDontSee('Send Greeting');
        $this->travelTo(Carbon::parse('2028-01-01 09:00'));
        Livewire::test(TaskNotifications::class)->assertSee('Send Greeting')->assertDontSee('Greeting Sent');
    }

    public function test_birthday_composer_rejects_ineligible_and_changed_recipients(): void
    {
        $this->travelTo(Carbon::parse('2026-02-26 09:00'));
        $leapDay = $this->senior('LeapDay', '1944-02-29');
        $senior = $this->senior('Changed', '1945-02-26');
        $form = route('sms.messages.birthday', $senior);
        $send = route('sms.messages.birthday.store', $senior);
        $payload = ['birthday_year' => 2026, 'recipient_number' => '900-000-0001', 'message' => 'Synthetic greeting'];

        $this->actingAs(User::factory()->create());
        $this->get(route('sms.messages.birthday', $leapDay))->assertConflict();
        $this->get($form)->assertOk();
        $senior->update(['contact_number' => '+639000000002']);
        $this->post($send, $payload)->assertConflict();
        $senior->update(['status' => 'DECEASED']);
        $this->get($form)->assertConflict();
        $this->assertDatabaseEmpty('sms_messages');

        $this->travelTo(Carbon::parse('2028-02-29 09:00'));
        Livewire::test(TaskNotifications::class)->assertSeeInOrder(['Synthetic LeapDay', 'Turning 84', 'Today']);
    }

    public function test_administration_cannot_open_or_send_a_staff_birthday_greeting(): void
    {
        $senior = $this->senior('StaffOnly', '1945-02-26');
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('sms.messages.birthday', $senior))->assertForbidden();
        $this->post(route('sms.messages.birthday.store', $senior))->assertForbidden();
    }

    public function test_failed_delivery_can_retry_the_same_greeting_with_the_current_registered_number(): void
    {
        $this->travelTo(Carbon::parse('2026-02-26 09:00'));
        $senior = $this->senior('Retry', '1945-02-26');
        $this->actingAs(User::factory()->create());
        $send = route('sms.messages.birthday.store', $senior);
        $message = 'Synthetic birthday greeting';
        $this->post($send, ['birthday_year' => 2026, 'recipient_number' => '900-000-0001', 'message' => $message])->assertRedirect();
        $queued = SmsMessage::sole();
        $senior->update(['contact_number' => '+639000000002']);
        $this->mock(SmsGateway::class)->expects('send')->once()->with('+639000000002', $message, 'sms-'.$queued->id)
            ->andReturn(['provider_message_id' => 'synthetic-retry-id', 'response' => []]);

        $this->artisan('queue:work', ['connection' => 'database-atomic', '--once' => true])->assertSuccessful();
        Livewire::test(TaskNotifications::class)->assertSee('Review failed greeting')->assertDontSee('Greeting Sent');
        $this->post(route('sms.messages.retry', $queued))->assertSessionHas('status', 'Failed SMS queued for retry.');
        $this->artisan('queue:work', ['connection' => 'database-atomic', '--once' => true])->assertSuccessful();

        $this->assertDatabaseCount('sms_messages', 1);
        Livewire::test(TaskNotifications::class)->assertSee('Greeting Sent');
    }

    private function senior(string $name, string $birthDate, array $attributes = []): SeniorCitizen
    {
        $barangay = Barangay::firstOrCreate(['code' => 'SYN'], ['name' => 'Synthetic Barangay']);

        return SeniorCitizen::forceCreate([
            'barangay_id' => $barangay->id, 'first_name' => 'Synthetic', 'last_name' => $name,
            'birth_date' => $birthDate, 'sex' => 'FEMALE', 'address' => 'Synthetic address',
            'status' => 'VERIFIED', 'osca_id_number' => 'SYN-'.$name, 'contact_number' => '900-000-0001',
            'id_generated_at' => now(), ...$attributes,
        ]);
    }
}
