<?php

namespace Tests\Feature\Sms;

use App\Jobs\Sms\SendSmsJob;
use App\Models\Barangay;
use App\Models\SeniorCitizen;
use App\Models\SmsBlast;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SmsBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_broadcast_counts_exclusions_and_queues_only_valid_verified_recipients(): void
    {
        Queue::fake();
        $barangay = Barangay::create(['name' => 'Poblacion', 'code' => 'POB']);
        foreach ([['VERIFIED', '09171234567'], ['VERIFIED', null], ['DECEASED', '09181234567']] as $index => [$status, $mobile]) {
            SeniorCitizen::create(['barangay_id' => $barangay->id, 'registration_number' => 'SC-TEST-'.$index, 'first_name' => 'Test', 'last_name' => 'Senior'.$index, 'birth_date' => '1948-01-01', 'sex' => 'FEMALE', 'address' => 'Test address', 'status' => $status, 'contact_number' => $mobile]);
        }
        $staff = User::factory()->create();

        $this->actingAs($staff)->get(route('sms.blasts.create'))->assertOk()->assertSee('Eligible recipients')->assertSee('>2</strong>', false);
        $this->post(route('sms.blasts.store'), ['message' => 'Reminder', 'confirmed' => '1'])->assertRedirect();

        $this->assertDatabaseCount('sms_messages', 1);
        $blast = SmsBlast::firstOrFail();
        $this->assertSame(1, $blast->recipient_count);
        $this->assertSame(2, $blast->excluded_count);
        $this->get(route('sms.blasts.show', $blast))->assertOk()->assertSee('Reminder');
        $this->assertDatabaseHas('sms_messages', ['recipient_number' => '+639171234567']);
        Queue::assertPushed(SendSmsJob::class, 1);
    }
}
