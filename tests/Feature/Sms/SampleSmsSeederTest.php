<?php

namespace Tests\Feature\Sms;

use App\Models\Barangay;
use App\Models\Beneficiary;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Models\SmsMessage;
use App\Models\User;
use Database\Seeders\SampleSmsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class SampleSmsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_sample_sent_history_is_visible_and_rerunning_does_not_duplicate_or_send_sms(): void
    {
        $this->freezeTime();
        $senior = $this->beneficiary();
        Queue::fake();
        Http::preventStrayRequests();
        Http::fake();

        $this->seed(SampleSmsSeeder::class);
        $this->seed(SampleSmsSeeder::class);

        $this->assertDatabaseCount('sms_messages', 1);
        $this->assertDatabaseCount('sms_delivery_logs', 1);
        $this->assertDatabaseHas('sms_messages', [
            'senior_citizen_id' => $senior->id, 'status' => 'SENT',
            'recipient_number' => 'MOCK-RECIPIENT-1', 'sent_at' => now()->subHour(),
        ]);
        $this->assertDatabaseHas('sms_delivery_logs', ['status' => 'SENT']);
        $this->actingAs(User::factory()->create())->get(route('sms.messages.index'))
            ->assertSee('[MOCK SMS]')->assertSee('SYNTHETIC BENEFICIARY')->assertSee('Sent');
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_only_active_synthetic_beneficiaries_receive_sample_history(): void
    {
        $this->beneficiary('Ordinary address');
        $inactive = $this->beneficiary();
        $inactive->beneficiaries()->update(['status' => 'INACTIVE']);

        $this->seed(SampleSmsSeeder::class);

        $this->assertDatabaseCount('sms_messages', 0);
        $this->assertDatabaseCount('sms_delivery_logs', 0);
    }

    public function test_sample_history_is_limited_to_twelve_beneficiaries(): void
    {
        for ($index = 0; $index < 13; $index++) {
            $this->beneficiary();
        }

        $this->seed(SampleSmsSeeder::class);

        $this->assertDatabaseCount('sms_messages', 12);
        $this->assertDatabaseCount('sms_delivery_logs', 12);
        $this->assertSame(3, SmsMessage::query()->distinct()->count('message'));
    }

    public function test_sample_sms_is_refused_in_production(): void
    {
        $this->app->instance('env', 'production');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Sample SMS data is only available outside production.');

        app()->call([new SampleSmsSeeder, 'run']);
    }

    private function beneficiary(string $address = 'SYNTHETIC ADDRESS - NOT A RESIDENCE'): SeniorCitizen
    {
        $barangay = Barangay::firstOrCreate(['code' => 'SMS-DEMO'], ['name' => 'Synthetic barangay']);
        $program = Program::firstOrCreate(['name' => 'Synthetic benefit'], ['agency' => 'OSCA', 'status' => 'ACTIVE']);
        $senior = SeniorCitizen::create([
            'barangay_id' => $barangay->id, 'first_name' => 'SYNTHETIC', 'last_name' => 'BENEFICIARY',
            'birth_date' => '1950-01-01', 'sex' => 'FEMALE', 'address' => $address, 'status' => 'VERIFIED',
        ]);
        Beneficiary::create(['program_id' => $program->id, 'senior_citizen_id' => $senior->id, 'status' => 'ACTIVE', 'enrolled_on' => today()]);

        return $senior;
    }
}
