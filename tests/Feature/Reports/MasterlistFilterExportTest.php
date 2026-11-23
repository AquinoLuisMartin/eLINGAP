<?php

namespace Tests\Feature\Reports;

use App\Models\Barangay;
use App\Models\SeniorCitizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class MasterlistFilterExportTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['csv'])]
    #[TestWith(['xlsx'])]
    #[TestWith(['pdf'])]
    public function test_generated_masterlist_contains_only_the_selected_barangay_status_and_age_range(string $format): void
    {
        $this->travelTo('2026-11-13 10:00:00');
        $barangay = Barangay::create(['name' => 'Sample North', 'code' => 'N']);
        $other = Barangay::create(['name' => 'Sample South', 'code' => 'S']);
        foreach ([
            ['MATCH-MIN', $barangay->id, 'VERIFIED', '1966-11-13'],
            ['MATCH-MAX', $barangay->id, 'VERIFIED', '1961-11-14'],
            ['OTHER-AREA', $other->id, 'VERIFIED', '1964-01-01'],
            ['OTHER-STATUS', $barangay->id, 'PENDING', '1964-01-01'],
            ['TOO-YOUNG', $barangay->id, 'VERIFIED', '1966-11-14'],
            ['TOO-OLD', $barangay->id, 'VERIFIED', '1961-11-13'],
        ] as [$identifier, $barangayId, $status, $birthDate]) {
            SeniorCitizen::create([
                'barangay_id' => $barangayId, 'osca_id_number' => $identifier,
                'first_name' => 'Sample', 'last_name' => $identifier, 'birth_date' => $birthDate,
                'sex' => 'FEMALE', 'address' => 'Sample address', 'status' => $status,
            ]);
        }
        $this->actingAs(User::factory()->create());
        $filters = ['type' => 'masterlist', 'barangay_id' => $barangay->id, 'status' => 'VERIFIED', 'age_min' => 60, 'age_max' => 64];

        $preview = $this->get(route('reports.workspace', $filters));
        $download = $this->get(route('reports.export', [...$filters, 'format' => $format]));

        $this->assertSame(['MATCH-MAX', 'MATCH-MIN'], $preview->viewData('records')->pluck('osca_id_number')->all());
        $download->assertOk()->assertDownload('Sample North - Senior citizens - 2026-11-13.'.$format);
        if ($format === 'pdf') {
            $pdf = $download->getContent();
            $this->assertStringStartsWith('%PDF-', $pdf);
            preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $streams);
            $contents = implode('', array_map(fn ($stream) => @gzuncompress($stream) ?: $stream, $streams[1]));
            foreach (['MATCH-MAX', 'MATCH-MIN', 'Sample North'] as $text) {
                $this->assertStringContainsString(mb_convert_encoding($text, 'UTF-16BE', 'UTF-8'), $contents);
            }
            foreach (['OTHER-AREA', 'OTHER-STATUS', 'TOO-YOUNG', 'TOO-OLD', 'Sample South', 'Municipality-wide verified population'] as $text) {
                $this->assertStringNotContainsString(mb_convert_encoding($text, 'UTF-16BE', 'UTF-8'), $contents);
            }
        } elseif ($format === 'xlsx') {
            $path = tempnam(storage_path('framework/testing'), 'masterlist-filter-');
            try {
                file_put_contents($path, $download->streamedContent());
                $book = IOFactory::load($path);
                $records = $book->getSheetByName('Records');
                $this->assertSame(3, $records->getHighestDataRow());
                $this->assertSame('MATCH-MAX', $records->getCell('A2')->getValue());
                $this->assertSame('MATCH-MIN', $records->getCell('A3')->getValue());
            } finally {
                if (isset($book)) {
                    $book->disconnectWorksheets();
                }
                unlink($path);
            }
        } else {
            $stream = fopen('php://temp', 'w+');
            fwrite($stream, $download->streamedContent());
            rewind($stream);
            fgetcsv($stream, escape: '');
            $identifiers = [];
            while (($row = fgetcsv($stream, escape: '')) !== false && $row !== [null]) {
                $identifiers[] = $row[0];
            }
            fclose($stream);
            $this->assertSame(['MATCH-MAX', 'MATCH-MIN'], $identifiers);
        }
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'report.exported', 'new_values->format' => $format,
            'new_values->filters->barangay_id' => (string) $barangay->id,
            'new_values->filters->status' => 'VERIFIED',
        ]);
    }
}
