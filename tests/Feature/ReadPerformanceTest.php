<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\Beneficiary;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReadPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_pagination_keeps_query_count_bounded_as_rows_increase(): void
    {
        $this->actingAs(User::factory()->create());
        $barangay = Barangay::create(['name' => 'Synthetic barangay', 'code' => 'SYN']);
        $program = Program::create(['name' => 'Synthetic program', 'agency' => 'Test agency', 'budget' => 1000, 'status' => 'ACTIVE']);
        $this->senior($barangay, 1, $program);
        DB::connection()->enableQueryLog();
        DB::connection()->flushQueryLog();
        $this->get(route('senior-citizens.index'))->assertOk();
        $singleQueries = count(DB::connection()->getQueryLog());
        foreach (range(2, 16) as $index) {
            $this->senior($barangay, $index, $program);
        }
        DB::connection()->flushQueryLog();

        $response = $this->get(route('senior-citizens.index'))->assertOk();
        $fullPageQueries = count(DB::connection()->getQueryLog());
        DB::connection()->disableQueryLog();

        $this->assertCount(15, $response->viewData('seniorCitizens'));
        $this->assertSame(16, $response->viewData('seniorCitizens')->total());
        $this->assertLessThanOrEqual($singleQueries + 1, $fullPageQueries, 'Queries must not grow with each rendered record.');
        $pageTwo = $this->get(route('senior-citizens.index', ['page' => 2]))->assertOk();
        $this->assertCount(1, $pageTwo->viewData('seniorCitizens'));
    }

    public function test_report_export_crosses_chunk_boundaries_without_omissions_or_duplicates(): void
    {
        $this->actingAs(User::factory()->create());
        $barangay = Barangay::create(['name' => 'Synthetic barangay', 'code' => 'SYN']);
        foreach (range(1, 205) as $index) {
            $this->senior($barangay, $index);
        }

        $response = $this->get(route('reports.export', ['type' => 'masterlist']))->assertOk();
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, $response->streamedContent());
        rewind($stream);
        $rows = [];
        while (($row = fgetcsv($stream, escape: '')) !== false) {
            $rows[] = $row;
        }
        fclose($stream);

        $this->assertCount(206, $rows);
        $ids = array_column(array_slice($rows, 1), 0);
        $this->assertCount(205, array_unique($ids));
        $this->assertSame('SYN-1', $ids[0]);
        $this->assertSame('SYN-205', $ids[204]);
    }

    private function senior(Barangay $barangay, int $index, ?Program $program = null): void
    {
        $senior = SeniorCitizen::create(['barangay_id' => $barangay->id, 'osca_id_number' => 'SYN-'.$index, 'first_name' => 'Synthetic', 'last_name' => 'Applicant '.$index, 'birth_date' => '1948-01-01', 'sex' => 'FEMALE', 'address' => 'Test address', 'status' => 'VERIFIED']);
        if ($program) {
            Beneficiary::create(['program_id' => $program->id, 'senior_citizen_id' => $senior->id, 'enrolled_on' => '2026-10-16', 'status' => 'ACTIVE']);
        }
    }
}
