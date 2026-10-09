<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\SeniorCitizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class InputSecurityTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['registration'])]
    #[TestWith(['update'])]
    #[TestWith(['sms'])]
    public function test_array_mobile_numbers_return_validation_errors_without_writing_records(string $operation): void
    {
        $staff = User::factory()->create();
        $this->actingAs($staff);
        if ($operation === 'update') {
            $barangay = Barangay::create(['name' => 'Test barangay', 'code' => 'TEST']);
            $senior = SeniorCitizen::create([
                'barangay_id' => $barangay->id, 'registration_number' => 'SC-INPUT-1',
                'first_name' => 'Synthetic', 'last_name' => 'Applicant', 'birth_date' => '1948-01-01',
                'sex' => 'FEMALE', 'address' => 'Test address',
            ]);
            $this->putJson(route('senior-citizens.update', $senior), ['contact_number' => ['unexpected']])
                ->assertUnprocessable()->assertJsonValidationErrors('contact_number');
        } else {
            $field = $operation === 'sms' ? 'recipient_number' : 'contact_number';
            $route = $operation === 'sms' ? 'sms.messages.store' : 'senior-citizens.store';
            $this->postJson(route($route), [$field => ['unexpected']])
                ->assertUnprocessable()->assertJsonValidationErrors($field);
        }

        $this->assertDatabaseCount('senior_citizens', $operation === 'update' ? 1 : 0);
        $this->assertDatabaseEmpty('sms_messages');
        $this->assertDatabaseEmpty('audit_logs');
    }
}
