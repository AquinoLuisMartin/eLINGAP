<?php

namespace Tests\Feature\Payouts;

use App\Models\Barangay;
use App\Models\Beneficiary;
use App\Models\PayoutSchedule;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PayoutScheduleBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_large_schedules_create_only_eligible_payouts_with_bounded_writes(): void
    {
        $this->actingAs(User::factory()->create());
        $program = Program::create(['name' => 'Synthetic batch program', 'agency' => 'Synthetic office', 'status' => 'ACTIVE']);
        $barangay = Barangay::create(['name' => 'Synthetic area', 'code' => 'BATCH']);
        $rows = [];
        for ($index = 1; $index <= 402; $index++) {
            $rows[] = ['barangay_id' => $barangay->id, 'first_name' => 'Synthetic', 'last_name' => 'Record',
                'birth_date' => '1948-01-01', 'sex' => 'FEMALE', 'address' => 'Synthetic address', 'status' => $index === 402 ? 'DECEASED' : 'VERIFIED'];
        }
        SeniorCitizen::insert($rows);
        Beneficiary::insert(SeniorCitizen::pluck('id')->map(fn ($id) => ['program_id' => $program->id, 'senior_citizen_id' => $id, 'enrolled_on' => '2026-01-01', 'status' => 'ACTIVE'])->all());
        DB::connection()->enableQueryLog();

        $response = $this->post(route('payout-schedules.store'), ['program_id' => $program->id, 'reference_number' => 'SYN-BATCH', 'scheduled_on' => '2026-12-10', 'amount' => '500']);

        $queries = DB::connection()->getQueryLog();
        DB::connection()->disableQueryLog();
        $writes = array_filter($queries, fn ($query) => str_starts_with(strtolower($query['query']), 'insert into "payouts"'));
        $this->assertLessThanOrEqual(3, count($writes));
        $response->assertRedirect(route('payout-schedules.show', PayoutSchedule::sole()));
        $this->assertDatabaseCount('payouts', 401);
        $this->assertSame(401, DB::table('payouts')->where('status', 'PENDING')->where('amount', '500.00')->whereNotNull('created_at')->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'payout.schedule_created']);
    }
}
