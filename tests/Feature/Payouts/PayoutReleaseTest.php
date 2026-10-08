<?php

namespace Tests\Feature\Payouts;

use App\Models\Barangay;
use App\Models\Beneficiary;
use App\Models\Payout;
use App\Models\PayoutSchedule;
use App\Models\Program;
use App\Models\Role;
use App\Models\SeniorCitizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayoutReleaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_release_requires_verification_and_cannot_be_repeated(): void
    {
        $staff = $this->user('OSCA_STAFF');
        $payout = $this->payout();

        $this->actingAs($staff)->patch(route('payouts.status.update', $payout), [
            'status' => 'RELEASED', 'claimant_type' => 'SELF',
        ])->assertSessionHasErrors('osca_id_checked');

        $payload = ['status' => 'RELEASED', 'claimant_type' => 'SELF', 'osca_id_checked' => '1'];
        $this->patch(route('payouts.status.update', $payout), $payload)->assertRedirect();
        $this->assertDatabaseHas('payouts', ['id' => $payout->id, 'status' => 'RELEASED', 'released_by' => $staff->id, 'claimant_type' => 'SELF']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payout.released', 'auditable_id' => $payout->id]);

        $this->patch(route('payouts.status.update', $payout), $payload)->assertStatus(409);
    }

    public function test_proxy_release_requires_representative_details(): void
    {
        $payout = $this->payout();
        $this->actingAs($this->user('OSCA_STAFF'))->patch(route('payouts.status.update', $payout), [
            'status' => 'RELEASED', 'claimant_type' => 'PROXY', 'osca_id_checked' => '1',
        ])->assertSessionHasErrors(['claimant_name', 'claimant_relationship', 'claimant_contact', 'authorization_checked', 'representative_id_checked']);
        $this->assertSame('PENDING', $payout->fresh()->status->value);
    }

    public function test_ineligible_senior_cannot_receive_payout(): void
    {
        $payout = $this->payout();
        $payout->seniorCitizen->update(['status' => 'ARCHIVED']);

        $this->actingAs($this->user('OSCA_STAFF'))->patch(route('payouts.status.update', $payout), [
            'status' => 'RELEASED', 'claimant_type' => 'SELF', 'osca_id_checked' => '1',
        ])->assertStatus(422);
        $this->assertSame('PENDING', $payout->fresh()->status->value);
    }

    public function test_inactive_beneficiary_cannot_receive_payout(): void
    {
        $payout = $this->payout();
        Beneficiary::query()->where('senior_citizen_id', $payout->senior_citizen_id)->update(['status' => 'INACTIVE']);

        $this->actingAs($this->user('OSCA_STAFF'))->patch(route('payouts.status.update', $payout), [
            'status' => 'RELEASED', 'claimant_type' => 'SELF', 'osca_id_checked' => '1',
        ])->assertStatus(422);
        $this->assertSame('PENDING', $payout->fresh()->status->value);
    }

    public function test_inactive_program_cannot_release_payout(): void
    {
        $payout = $this->payout();
        $payout->schedule->program->update(['status' => 'INACTIVE']);

        $this->actingAs($this->user('OSCA_STAFF'))->patch(route('payouts.status.update', $payout), [
            'status' => 'RELEASED', 'claimant_type' => 'SELF', 'osca_id_checked' => '1',
        ])->assertStatus(422);
        $this->assertSame('PENDING', $payout->fresh()->status->value);
    }

    public function test_staff_cannot_reverse_a_payout(): void
    {
        $payout = $this->payout();
        $payout->update(['status' => 'RELEASED', 'released_at' => now()]);

        $this->actingAs($this->user('OSCA_STAFF'))->patch(route('payouts.reverse', $payout), ['reason' => 'Duplicate release'])->assertForbidden();
        $this->assertSame('RELEASED', $payout->fresh()->status->value);
    }

    public function test_admin_reversal_requires_reason_and_is_audited(): void
    {
        $payout = $this->payout();
        $payout->update(['status' => 'RELEASED', 'released_at' => now()]);

        $this->actingAs($this->user('ADMIN'))->patch(route('payouts.reverse', $payout), ['reason' => ''])->assertRedirect();
        $this->assertSame('RELEASED', $payout->fresh()->status->value);
        $this->patch(route('payouts.reverse', $payout), ['reason' => 'Duplicate release'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('VOIDED', $payout->fresh()->status->value);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payout.reversed', 'auditable_id' => $payout->id]);
    }

    public function test_schedule_creates_payouts_only_for_active_verified_beneficiaries(): void
    {
        $payout = $this->payout();
        $program = $payout->schedule->program;
        $archived = $payout->seniorCitizen->replicate();
        $archived->registration_number = 'SC-TEST-002';
        $archived->status = 'DECEASED';
        $archived->save();
        Beneficiary::create(['program_id' => $program->id, 'senior_citizen_id' => $archived->id, 'enrolled_on' => today(), 'status' => 'ACTIVE']);

        $this->actingAs($this->user('OSCA_STAFF'))->post(route('payout-schedules.store'), [
            'program_id' => $program->id, 'reference_number' => 'SCH-TEST-002', 'scheduled_on' => today()->toDateString(), 'amount' => 1500,
        ])->assertRedirect();

        $schedule = PayoutSchedule::query()->where('reference_number', 'SCH-TEST-002')->firstOrFail();
        $this->assertSame(1, $schedule->payouts()->count());
        $this->assertDatabaseHas('payouts', ['payout_schedule_id' => $schedule->id, 'senior_citizen_id' => $payout->senior_citizen_id, 'amount' => 1500]);
    }

    private function user(string $role): User
    {
        $record = Role::firstOrCreate(['name' => $role], ['description' => $role]);

        return User::create(['role_id' => $record->id, 'username' => strtolower($role).'user', 'email' => strtolower($role).'@example.test', 'password_hash' => 'password', 'first_name' => 'Test', 'last_name' => 'User', 'is_active' => true]);
    }

    private function payout(): Payout
    {
        $barangay = Barangay::create(['name' => 'Poblacion', 'code' => 'POB']);
        $senior = SeniorCitizen::create(['barangay_id' => $barangay->id, 'registration_number' => 'SC-TEST-001', 'first_name' => 'Maria', 'last_name' => 'Santos', 'birth_date' => '1948-01-01', 'sex' => 'FEMALE', 'address' => 'Poblacion', 'status' => 'VERIFIED']);
        $program = Program::create(['name' => 'Assistance', 'agency' => 'OSCA', 'budget' => 10000, 'status' => 'ACTIVE']);
        $schedule = PayoutSchedule::create(['program_id' => $program->id, 'reference_number' => 'SCH-TEST-001', 'scheduled_on' => today(), 'amount' => 1000]);
        Beneficiary::create(['program_id' => $program->id, 'senior_citizen_id' => $senior->id, 'enrolled_on' => today(), 'status' => 'ACTIVE']);

        return Payout::create(['payout_schedule_id' => $schedule->id, 'senior_citizen_id' => $senior->id, 'amount' => 1000, 'status' => 'PENDING']);
    }
}
