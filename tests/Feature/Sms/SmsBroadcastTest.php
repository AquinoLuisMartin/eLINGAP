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
            SeniorCitizen::create(['barangay_id' => $barangay->id, 'first_name' => 'Test', 'last_name' => 'Senior'.$index, 'birth_date' => '1948-01-01', 'sex' => 'FEMALE', 'address' => 'Test address', 'status' => $status, 'contact_number' => $mobile]);
        }
        $staff = User::factory()->create();

        $this->actingAs($staff)->get(route('sms.blasts.create'))->assertOk()->assertSee('Eligible recipients')->assertSee('>2</strong>', false);
        $this->post(route('sms.blasts.store'), ['message' => 'Reminder', 'confirmed' => '1'])->assertRedirect();

        $this->assertDatabaseCount('sms_messages', 1);
        $blast = SmsBlast::firstOrFail();
        $this->assertSame(1, $blast->recipient_count);
        $this->assertSame(2, $blast->excluded_count);
        $blast->messages()->sole()->update(['status' => 'SENT']);
        $response = $this->get(route('sms.blasts.show', $blast))->assertOk()->assertSee('Reminder')->assertSee('Sent');
        $document = new \DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        $this->assertCount(0, $xpath->query('//table/tbody/tr/td[last()]//button'));
        $this->get(route('sms.blasts.index'))->assertSeeHtml('class="secondary-button" href="'.route('sms.blasts.show', $blast).'">Details</a>');
        $this->assertDatabaseHas('sms_messages', ['recipient_number' => '+639171234567']);
        Queue::assertPushed(SendSmsJob::class, 1);
    }
}
