<?php

namespace Tests\Feature\Reports;

use App\Models\Barangay;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ReportFilenameTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['masterlist', 'Senior citizens'])]
    #[TestWith(['beneficiaries', 'Program beneficiaries'])]
    #[TestWith(['population', 'Senior population by barangay'])]
    #[TestWith(['applications', 'Benefit application status log'])]
    #[TestWith(['payouts', 'Benefits and payout summary'])]
    #[TestWith(['milestones', 'Milestone age qualifiers'])]
    #[TestWith(['proxy', 'Proxy payout claims'])]
    #[TestWith(['archived', 'Deceased and archived seniors log'])]
    public function test_unfiltered_exports_use_the_report_description_and_generation_date(string $type, string $description): void
    {
        $this->travelTo('2026-11-15 10:00:00');
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('reports.export', ['type' => $type]));

        $response->assertOk()->assertDownload('All Barangays - '.$description.' - 2026-11-15.csv');
    }

    #[TestWith(['csv'])]
    #[TestWith(['pdf'])]
    #[TestWith(['xlsx'])]
    public function test_export_names_sanitize_barangay_and_program_labels(string $format): void
    {
        $this->travelTo('2026-11-15 10:00:00');
        $barangay = Barangay::create(['name' => 'Synthetic / North\\Area', 'code' => 'SYN']);
        $program = Program::create(['name' => "Synthetic: \"Aid\"\r\n<2026>|?*%", 'agency' => 'Test', 'status' => 'ACTIVE']);
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('reports.export', [
            'type' => 'beneficiaries', 'barangay_id' => $barangay->id, 'program_id' => $program->id, 'format' => $format,
        ]));

        $response->assertOk()->assertDownload('Synthetic North Area - Synthetic Aid 2026 - Program beneficiaries - 2026-11-15.'.$format);
    }
}
