<?php

namespace Tests\Feature\Reports;

use App\Models\Barangay;
use App\Models\SeniorCitizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class CsvSecurityTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['=1+1', "'=1+1 Applicant"])]
    #[TestWith(['  =1+1', "'  =1+1 Applicant"])]
    #[TestWith(["\n=1+1", "'\n=1+1 Applicant"])]
    #[TestWith(['Synthetic', 'Synthetic Applicant'])]
    public function test_csv_exports_escape_formula_cells_and_preserve_plain_text(string $name, string $expected): void
    {
        $staff = User::factory()->create();
        $barangay = Barangay::create(['name' => 'Test barangay', 'code' => 'TEST']);
        SeniorCitizen::create([
            'barangay_id' => $barangay->id, 'registration_number' => 'SC-CSV-1',
            'first_name' => $name, 'last_name' => 'Applicant', 'birth_date' => '1948-01-01',
            'sex' => 'FEMALE', 'address' => 'Test address',
        ]);

        $response = $this->actingAs($staff)->get(route('reports.export', ['type' => 'masterlist']))->assertOk();
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, $response->streamedContent());
        rewind($stream);
        fgetcsv($stream, escape: '');
        $row = fgetcsv($stream, escape: '');
        fclose($stream);

        $this->assertSame($expected, $row[1]);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }
}
