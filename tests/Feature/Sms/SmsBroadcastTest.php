<?php

namespace Tests\Feature\Sms;

use App\Jobs\Sms\SendSmsJob;
use App\Models\Barangay;
use App\Models\Beneficiary;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Models\SmsBlast;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SmsBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_program_audience_counts_and_delivery_match_active_masterlist_assignments(): void
    {
        Queue::fake();
        $staff = User::factory()->create();
        $barangay = Barangay::create(['name' => 'Preview area', 'code' => 'PREVIEW']);
        $other = Barangay::create(['name' => 'Other area', 'code' => 'OTHER']);
        $program = Program::create(['name' => 'Preview assistance', 'agency' => 'OSCA', 'budget' => 0]);
        $selected = null;
        foreach ([['ACTIVE', '09171234567', $barangay->id], ['ACTIVE', null, $barangay->id], ['INACTIVE', '09181234567', $barangay->id], ['ACTIVE', '09191234567', $other->id]] as $index => [$status, $mobile, $area]) {
            $senior = SeniorCitizen::create(['barangay_id' => $area, 'first_name' => 'Preview', 'last_name' => 'Record'.$index, 'birth_date' => '1948-01-01', 'sex' => 'FEMALE', 'address' => 'Preview address', 'status' => 'VERIFIED', 'contact_number' => $mobile]);
            Beneficiary::create(['program_id' => $program->id, 'senior_citizen_id' => $senior->id, 'enrolled_on' => today(), 'status' => $status]);
            if ($index === 0) {
                $selected = $senior;
            }
        }
        $filters = ['barangay_id' => $barangay->id, 'program_id' => $program->id];
        $this->actingAs($staff)->getJson(route('sms.blasts.create', $filters))->assertOk()->assertExactJson(['eligible' => 1, 'excluded' => 1]);
        $emptyProgram = Program::create(['name' => 'Other assistance', 'agency' => 'OSCA', 'budget' => 0]);
        $this->getJson(route('sms.blasts.create', ['program_id' => $emptyProgram->id]))->assertOk()->assertExactJson(['eligible' => 0, 'excluded' => 0]);
        $this->post(route('sms.blasts.store'), [...$filters, 'message' => 'Preview reminder', 'confirmed' => '1'])->assertRedirect();
        $this->assertDatabaseCount('sms_messages', 1);
        $this->assertDatabaseHas('sms_messages', ['senior_citizen_id' => $selected->id]);
        $this->assertSame(1, SmsBlast::sole()->recipient_count);
        $this->assertSame(1, SmsBlast::sole()->excluded_count);
    }

    public function test_invalid_program_filters_are_rejected_for_preview_and_delivery(): void
    {
        $this->actingAs(User::factory()->create());
        $this->getJson(route('sms.blasts.create', ['program_id' => 999999]))->assertUnprocessable()->assertJsonValidationErrors('program_id');
        $this->post(route('sms.blasts.store'), ['program_id' => 999999, 'message' => 'Preview', 'confirmed' => '1'])->assertSessionHasErrors('program_id');
        $this->assertDatabaseCount('sms_messages', 0);
    }

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
        $response->assertSee('Broadcast details')->assertDontSee('Back to broadcasts')->assertDontSee('Broadcast #'.$blast->id);
        $this->assertCount(0, $xpath->query('//strong[normalize-space(.)="Message:"]'));
        $back = $xpath->query('//header/a[normalize-space(.)="← Back"]');
        $this->assertCount(1, $back);
        $this->assertSame(route('sms.blasts.index'), $back->item(0)->getAttribute('href'));
        $this->assertCount(0, $xpath->query('//table/tbody/tr/td[last()]//button'));
        $this->get(route('sms.blasts.index'))->assertSeeHtml('class="secondary-button" href="'.route('sms.blasts.show', $blast).'">Details</a>');
        $this->assertDatabaseHas('sms_messages', ['recipient_number' => '+639171234567']);
        Queue::assertPushed(SendSmsJob::class, 1);
    }
}
