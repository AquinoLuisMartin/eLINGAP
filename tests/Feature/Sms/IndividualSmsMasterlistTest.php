<?php

namespace Tests\Feature\Sms;

use App\Models\Barangay;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IndividualSmsMasterlistTest extends TestCase
{
    use RefreshDatabase;

    public function test_lookup_only_lists_reachable_beneficiaries_of_the_selected_program(): void
    {
        $staff = User::factory()->create();
        $program = Program::create(['name' => 'Synthetic selected program', 'agency' => 'Synthetic agency']);
        $otherProgram = Program::create(['name' => 'Synthetic other program', 'agency' => 'Synthetic agency']);
        $barangay = Barangay::create(['name' => 'Synthetic barangay', 'code' => 'SYN']);
        $eligible = $this->beneficiary($program, $barangay, 'Eligible', '09000000000');
        $this->beneficiary($program, $barangay, 'NoContact', null);
        $this->beneficiary($otherProgram, $barangay, 'OtherProgram', '09000000001');

        $this->actingAs($staff)->get(route('sms.messages.create'))->assertOk()->assertSee('Program masterlist')->assertSee($program->name);
        $this->getJson(route('sms.messages.recipients', ['program_id' => $program->id]))
            ->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertExactJson(['recipients' => [['id' => (string) $eligible->id, 'name' => 'ELIGIBLE, SYNTHETIC', 'osca_id' => null]], 'has_more' => false]);
        $this->getJson(route('sms.messages.recipients', ['program_id' => $program->id, 'search' => 'OtherProgram']))
            ->assertOk()->assertJsonCount(0, 'recipients');
    }

    public function test_direct_send_uses_the_saved_number_and_rejects_a_beneficiary_from_another_program(): void
    {
        config(['services.sms.url' => 'https://pinassms.com/api', 'services.sms.token' => 'synthetic-token']);
        Http::preventStrayRequests();
        Http::fake(['https://pinassms.com/api/sms/send' => Http::response(['id' => 'synthetic-message'], 202)]);
        $staff = User::factory()->create();
        $program = Program::create(['name' => 'Synthetic selected program', 'agency' => 'Synthetic agency']);
        $otherProgram = Program::create(['name' => 'Synthetic other program', 'agency' => 'Synthetic agency']);
        $barangay = Barangay::create(['name' => 'Synthetic barangay', 'code' => 'SYN']);
        $senior = $this->beneficiary($program, $barangay, 'Eligible', '09000000000');
        $message = ['senior_citizen_id' => $senior->id, 'recipient_number' => '09000000001', 'message' => 'Synthetic SMS'];

        $this->actingAs($staff)->postJson(route('sms.messages.store'), $message + ['program_id' => $otherProgram->id])
            ->assertUnprocessable()->assertJsonValidationErrors('senior_citizen_id');
        $this->assertDatabaseCount('sms_messages', 0);

        $this->post(route('sms.messages.store'), $message + ['program_id' => $program->id])->assertRedirectToRoute('sms.messages.index');
        Http::assertSent(fn ($request) => $request['recipient'] === '639000000000');
        Http::assertSentCount(1);
        $this->assertDatabaseHas('sms_messages', ['senior_citizen_id' => $senior->id, 'recipient_number' => '+639000000000', 'status' => 'SENT']);
        $this->assertDatabaseCount('jobs', 0);
    }

    private function beneficiary(Program $program, Barangay $barangay, string $lastName, ?string $number): SeniorCitizen
    {
        $senior = SeniorCitizen::create(['barangay_id' => $barangay->id, 'first_name' => 'Synthetic', 'last_name' => $lastName, 'birth_date' => '1950-01-01', 'sex' => 'FEMALE', 'address' => 'Synthetic address', 'status' => 'VERIFIED', 'contact_number' => $number]);
        $senior->beneficiaries()->create(['program_id' => $program->id, 'status' => 'ACTIVE', 'enrolled_on' => today()]);

        return $senior;
    }
}
