<?php

namespace Tests\Feature\SeniorCitizens;

use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\SeniorCitizen;
use App\Models\User;
use App\Services\Benefits\SeniorEligibilityRecord;
use App\Services\SeniorCitizens\SeniorImportReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class SeniorImportTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['csv'])]
    #[TestWith(['xlsx'])]
    #[TestWith(['xls'])]
    public function test_staff_can_preview_and_import_normalized_historical_records(string $format): void
    {
        $staff = User::factory()->create();
        $this->actingAs($staff);
        $this->get(route('registrations.index'))->assertSee('Import records');
        $this->get(route('registrations.import.create'))->assertSee('Import previous records');
        $row = [...$this->row(), 'representative_name' => 'Synthetic Representative', 'representative_relationship' => 'Child', 'representative_contact' => '967-234-8976'];
        $token = $this->preview([$row], $format);

        $this->get(route('registrations.import.show', $token))->assertOk()->assertSee('SYNTHETIC ÑANDÚ')
            ->assertSee('+639672348976')->assertSee('000123')->assertSee('Confirm import')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertDatabaseCount('senior_citizens', 0);
        $this->post(route('registrations.import.store', $token), ['reviewed' => 1, 'rows' => [['status' => 'VERIFIED']]])
            ->assertRedirect(route('senior-citizens.index', ['status' => 'PENDING']))->assertSessionHas('status', 'Import complete: 1 records added for review; 0 duplicates skipped.');

        $senior = SeniorCitizen::sole();
        $this->assertSame('SYNTHETIC ÑANDÚ', $senior->first_name);
        $this->assertSame('SYNTHETIC ADDRESS', $senior->address);
        $this->assertSame('JR.', $senior->name_suffix);
        $this->assertSame('+639672348976', $senior->contact_number);
        $this->assertSame('000123', $senior->osca_id_number);
        $this->assertSame('1950-01-02', $senior->birth_date->format('Y-m-d'));
        $this->assertSame('SYNTHETIC REPRESENTATIVE', $senior->representative_name);
        $this->assertSame('+639672348976', $senior->representative_contact);
        $this->assertSame('PENDING', $senior->status->value);
        $this->assertNull($senior->verified_at);
        $this->assertNull($senior->id_generated_at);
        $this->assertDatabaseCount('beneficiaries', 0);
        $this->assertDatabaseCount('sms_messages', 0);
        $this->assertDatabaseHas('senior_citizen_histories', ['action' => 'imported', 'changed_by' => $staff->id, 'senior_citizen_id' => $senior->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'senior_citizen.import_completed', 'user_id' => $staff->id]);
        $audit = AuditLog::sole();
        $this->assertSame(1, $audit->new_values['created']);
        $this->assertSame(1, $audit->new_values['historical_ids_preserved']);
        $this->assertSame($audit->new_values['batch_id'], $senior->histories()->sole()->changes['batch_id']);
        $this->post(route('registrations.import.store', $token), ['reviewed' => 1])->assertStatus(410);
        $this->assertDatabaseCount('senior_citizens', 1);
    }

    public function test_exact_duplicates_are_skipped_but_saved_records_are_not_overwritten(): void
    {
        $this->actingAs(User::factory()->create());
        $row = $this->row();
        $token = $this->preview([$row, $row]);
        $this->get(route('registrations.import.show', $token))->assertSee('1 ready, 1 duplicates skipped, 0 invalid.');
        $this->post(route('registrations.import.store', $token), ['reviewed' => 1])->assertRedirect();
        $senior = SeniorCitizen::sole();
        $token = $this->preview([[...$row, 'address' => 'Do not overwrite']]);
        $this->get(route('registrations.import.show', $token))->assertSee('0 ready, 1 duplicates skipped, 0 invalid.')->assertDontSee('Confirm import');
        $this->post(route('registrations.import.store', $token), ['reviewed' => 1])->assertRedirect();

        $this->assertSame('SYNTHETIC ADDRESS', $senior->fresh()->address);
        $this->assertDatabaseCount('senior_citizens', 1);
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_repeated_idless_rows_are_also_duplicates(): void
    {
        $this->actingAs(User::factory()->create());
        $row = [...$this->row(), 'osca_id_number' => ''];
        $token = $this->preview([$row, $row]);
        $this->post(route('registrations.import.store', $token), ['reviewed' => 1])->assertRedirect();
        $this->assertDatabaseCount('senior_citizens', 1);
        $this->assertNull(SeniorCitizen::sole()->osca_id_number);
    }

    #[TestWith(['contact_number', '8672348976'])]
    #[TestWith(['birth_date', '01/02/2020'])]
    #[TestWith(['birth_date', '02/30/1950'])]
    #[TestWith(['barangay', 'Unknown barangay'])]
    #[TestWith(['civil_status', 'UNLISTED'])]
    #[TestWith(['representative_contact', '123'])]
    #[TestWith(['osca_id_number', 'invalid@ID'])]
    public function test_invalid_rows_block_the_entire_import(string $field, string $value): void
    {
        $this->actingAs(User::factory()->create());
        $row = $this->row();
        $token = $this->preview([$row, [...$row, 'first_name' => 'Second Synthetic', 'osca_id_number' => '000124', $field => $value]]);
        $this->get(route('registrations.import.show', $token))->assertSee('Needs correction')->assertDontSee('Confirm import');
        $this->post(route('registrations.import.store', $token), ['reviewed' => 1])->assertSessionHasErrors('import');
        $this->assertDatabaseCount('senior_citizens', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_an_osca_id_cannot_be_assigned_to_two_different_people_in_a_file_or_database(): void
    {
        $this->actingAs(User::factory()->create());
        $row = $this->row();
        $token = $this->preview([$row, [...$row, 'first_name' => 'Other Synthetic']]);
        $this->get(route('registrations.import.show', $token))->assertSee('This OSCA ID is used for another senior in this file.');
        $this->post(route('registrations.import.store', $token), ['reviewed' => 1])->assertSessionHasErrors('import');
        $token = $this->preview([$row]);
        $this->post(route('registrations.import.store', $token), ['reviewed' => 1])->assertRedirect();
        $token = $this->preview([[...$row, 'first_name' => 'Other Synthetic']]);
        $this->get(route('registrations.import.show', $token))->assertSee('The OSCA ID conflicts with an existing record.');
        $this->post(route('registrations.import.store', $token), ['reviewed' => 1])->assertSessionHasErrors('import');
        $this->assertDatabaseCount('senior_citizens', 1);
    }

    public function test_rows_with_the_same_identity_and_different_osca_ids_are_blocked(): void
    {
        $this->actingAs(User::factory()->create());
        $row = $this->row();
        $token = $this->preview([$row, [...$row, 'osca_id_number' => '000124']]);
        $this->get(route('registrations.import.show', $token))->assertSee('Rows with the same identity have different OSCA IDs.');
        $this->post(route('registrations.import.store', $token), ['reviewed' => 1])->assertSessionHasErrors('import');
        $this->assertDatabaseCount('senior_citizens', 0);
    }

    public function test_confirmation_rechecks_barangay_availability(): void
    {
        $this->actingAs(User::factory()->create());
        $token = $this->preview([$this->row()]);
        Barangay::query()->update(['is_active' => false]);
        $this->post(route('registrations.import.store', $token), ['reviewed' => 1])->assertSessionHasErrors('import');
        $this->assertDatabaseCount('senior_citizens', 0);
    }

    public function test_a_record_added_after_preview_is_skipped_at_confirmation(): void
    {
        $this->actingAs(User::factory()->create());
        $row = $this->row();
        $first = $this->preview([$row]);
        $second = $this->preview([$row]);
        $this->post(route('registrations.import.store', $first), ['reviewed' => 1])->assertRedirect();
        $this->post(route('registrations.import.store', $second), ['reviewed' => 1])->assertSessionHas('status', 'Import complete: 0 records added for review; 1 duplicates skipped.');
        $this->assertDatabaseCount('senior_citizens', 1);
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_a_failed_summary_audit_rolls_back_the_whole_batch_and_allows_retry(): void
    {
        $this->actingAs(User::factory()->create());
        $row = $this->row();
        $token = $this->preview([$row, [...$row, 'first_name' => 'Second Synthetic', 'osca_id_number' => '000124']]);
        Event::listen('eloquent.creating: '.AuditLog::class, function (AuditLog $audit): void {
            if ($audit->action === 'senior_citizen.import_completed') {
                throw new RuntimeException('Synthetic import audit failure');
            }
        });
        Exceptions::fake([RuntimeException::class]);

        $this->post(route('registrations.import.store', $token), ['reviewed' => 1])->assertStatus(500);

        Exceptions::assertReported(RuntimeException::class);
        $this->assertDatabaseCount('senior_citizens', 0);
        $this->assertDatabaseCount('senior_citizen_histories', 0);
        $this->assertDatabaseCount('audit_logs', 0);
        $this->get(route('registrations.import.show', $token))->assertOk();
        Event::forget('eloquent.creating: '.AuditLog::class);
        $this->post(route('registrations.import.store', $token), ['reviewed' => 1])->assertRedirect();
        $this->assertDatabaseCount('senior_citizens', 2);
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_only_active_staff_can_access_imports_and_templates(): void
    {
        $token = (string) Str::uuid();
        $this->get(route('registrations.import.create'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->admin()->create());
        $this->get(route('registrations.import.create'))->assertForbidden();
        $this->get(route('registrations.import.template', 'csv'))->assertForbidden();
        $this->post(route('registrations.import.preview'))->assertForbidden();
        $this->get(route('registrations.import.show', $token))->assertForbidden();
        $this->post(route('registrations.import.store', $token), ['reviewed' => 1])->assertForbidden();
        $this->delete(route('registrations.import.destroy', $token))->assertForbidden();
        $this->actingAs(User::factory()->inactive()->create())->get(route('registrations.import.create'))->assertRedirect(route('login'));
        $this->assertDatabaseCount('senior_citizens', 0);
    }

    public function test_previews_are_owner_bound_encrypted_expiring_and_can_be_discarded(): void
    {
        $staff = User::factory()->create();
        $this->actingAs($staff);
        $token = $this->preview([$this->row()]);
        $encrypted = Cache::get('senior-import:'.$staff->id.':'.$token);
        $this->assertIsString($encrypted);
        $this->assertStringNotContainsString('synthetic', $encrypted);
        $other = User::factory()->create();
        $this->actingAs($other)->withSession(['password_hash_web' => $other->password_hash])->get(route('registrations.import.show', $token))->assertStatus(410);
        $this->post(route('registrations.import.store', $token), ['reviewed' => 1])->assertStatus(410);
        $this->actingAs($staff)->withSession(['password_hash_web' => $staff->password_hash])->post(route('registrations.import.store', $token))->assertSessionHasErrors('reviewed');
        $this->travel(31)->minutes();
        $this->get(route('registrations.import.show', $token))->assertStatus(410)->assertSee('Upload a file');
        $token = $this->preview([$this->row()]);
        $this->delete(route('registrations.import.destroy', $token))->assertRedirect(route('registrations.import.create'));
        $this->get(route('registrations.import.show', $token))->assertStatus(410);
        $this->assertDatabaseCount('senior_citizens', 0);
    }

    #[TestWith(['csv'])]
    #[TestWith(['xlsx'])]
    #[TestWith(['xls'])]
    public function test_downloaded_templates_are_supported(string $format): void
    {
        $this->actingAs(User::factory()->create());
        $response = $this->get(route('registrations.import.template', $format))->assertDownload('senior-import-template.'.$format);
        $template = $response->streamedContent();
        $this->assertNotEmpty($template);
        $this->post(route('registrations.import.preview'), ['file' => UploadedFile::fake()->createWithContent('template.'.$format, $template)])
            ->assertSessionHasErrors(['file' => 'The file contains no records. Keep the header and add at least one senior.']);
        $this->assertDatabaseCount('senior_citizens', 0);
    }

    #[TestWith([1900])]
    #[TestWith([1904])]
    public function test_excel_dates_and_displayed_leading_zero_ids_are_preserved(int $calendar): void
    {
        $this->actingAs(User::factory()->create());
        $row = [...$this->row(), 'birth_date' => Date::PHPToExcel(new \DateTimeImmutable('1950-01-02')) - ($calendar === 1904 ? 1462 : 0), 'osca_id_number' => 123];
        $token = $this->preview([$row], 'xlsx', function (Spreadsheet $book) use ($calendar): void {
            $book->setExcelCalendar($calendar);
            $book->getActiveSheet()->getStyle('K2')->getNumberFormat()->setFormatCode('000000');
        });
        $this->post(route('registrations.import.store', $token), ['reviewed' => 1])->assertRedirect();
        $senior = SeniorCitizen::sole();
        $this->assertSame('000123', $senior->osca_id_number);
        $this->assertSame('1950-01-02', $senior->birth_date->format('Y-m-d'));
    }

    #[TestWith(['26-0088'])]
    #[TestWith(['0'])]
    public function test_review_and_id_issuance_keep_the_imported_number(string $number): void
    {
        $this->actingAs(User::factory()->create());
        $token = $this->preview([[...$this->row(), 'osca_id_number' => $number]]);
        $this->post(route('registrations.import.store', $token), ['reviewed' => 1])->assertRedirect();
        $senior = SeniorCitizen::sole();
        $records = app(SeniorEligibilityRecord::class);
        $records->verify($senior, true);
        $records->issueId($senior->fresh());

        $this->assertSame($number, $senior->fresh()->osca_id_number);
        $this->assertNotNull($senior->fresh()->id_generated_at);
        $this->assertDatabaseCount('osca_id_numbers', 0);
    }

    public function test_500_rows_are_supported_with_batched_duplicate_queries_and_paginated_preview(): void
    {
        $this->actingAs(User::factory()->create());
        $row = $this->row();
        $rows = [];
        for ($index = 0; $index < 500; $index++) {
            $rows[] = [...$row, 'first_name' => 'Synthetic '.$index, 'osca_id_number' => 'LEGACY-'.$index];
        }
        $queries = 0;
        DB::listen(function ($query) use (&$queries): void {
            if (str_starts_with($query->sql, 'select') && str_contains($query->sql, '"senior_citizens"')) {
                $queries++;
            }
        });
        $token = $this->preview($rows);
        $this->assertLessThanOrEqual(2, $queries);
        $this->get(route('registrations.import.show', $token))->assertSee('500 ready')->assertSee('SYNTHETIC 0')->assertDontSee('SYNTHETIC 50');
        $this->get(route('registrations.import.show', [$token, 'page' => 10]))->assertSee('SYNTHETIC 499');
        $this->post(route('registrations.import.store', $token), ['reviewed' => 1])->assertRedirect();
        $this->assertDatabaseCount('senior_citizens', 500);
        $this->assertDatabaseCount('audit_logs', 1);
        $this->assertDatabaseCount('senior_citizen_histories', 500);
        $audit = AuditLog::sole();
        $this->assertSame(500, $audit->new_values['created']);
        $this->assertSame(500, DB::table('senior_citizen_histories')->where('changes->batch_id', $audit->new_values['batch_id'])->count());
    }

    public function test_import_content_is_escaped_and_a_busy_preview_cannot_be_confirmed_or_discarded(): void
    {
        $staff = User::factory()->create();
        $this->actingAs($staff);
        $token = $this->preview([[...$this->row(), 'first_name' => '<script>synthetic</script>']]);
        $this->get(route('registrations.import.show', $token))->assertSee('<SCRIPT>SYNTHETIC</SCRIPT>')->assertDontSee('<SCRIPT>SYNTHETIC</SCRIPT>', false);
        $lock = Cache::lock('senior-import:'.$staff->id.':'.$token.':lock', 120);
        $this->assertTrue($lock->get());
        try {
            $this->post(route('registrations.import.store', $token), ['reviewed' => 1])->assertStatus(409);
            $this->delete(route('registrations.import.destroy', $token))->assertStatus(409);
            $this->assertDatabaseCount('senior_citizens', 0);
        } finally {
            $lock->release();
        }
    }

    public function test_unknown_headers_formulas_multiple_sheets_and_excess_rows_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $row = $this->row();
        $this->post(route('registrations.import.preview'), ['file' => $this->file([$row], 'xlsx', function (Spreadsheet $book): void {
            $book->getActiveSheet()->setCellValue('A2', '=1+1');
        })])->assertSessionHasErrors('file');
        $this->post(route('registrations.import.preview'), ['file' => $this->file([$row], 'xlsx', function (Spreadsheet $book): void {
            $book->createSheet();
        })])->assertSessionHasErrors('file');
        $this->post(route('registrations.import.preview'), ['file' => $this->file([$row], 'xlsx', function (Spreadsheet $book): void {
            $book->getActiveSheet()->setCellValue('A1', 'status');
        })])->assertSessionHasErrors('file');
        $this->post(route('registrations.import.preview'), ['file' => $this->file(array_fill(0, 501, $row), 'csv')])->assertSessionHasErrors('file');
        $this->post(route('registrations.import.preview'), ['file' => UploadedFile::fake()->createWithContent('fake.xlsx', '<html>not an Excel file</html>')])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('senior_citizens', 0);
    }

    public function test_oversized_files_macros_and_excessive_archive_expansion_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('registrations.import.preview'), ['file' => UploadedFile::fake()->create('large.xlsx', 5121, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')])->assertSessionHasErrors('file');
        $row = $this->row();
        foreach (['xl/vbaProject.bin' => 'synthetic macro', 'oversized.xml' => str_repeat('x', 20 * 1024 * 1024 + 1)] as $entry => $content) {
            $file = $this->file([$row], 'xlsx');
            $path = tempnam(sys_get_temp_dir(), 'elingap-import-');
            try {
                file_put_contents($path, file_get_contents($file->getPathname()));
                $zip = new ZipArchive;
                $this->assertTrue($zip->open($path));
                $zip->addFromString($entry, $content);
                $zip->close();
                $file = UploadedFile::fake()->createWithContent('synthetic.xlsx', file_get_contents($path));
            } finally {
                unlink($path);
            }

            $this->post(route('registrations.import.preview'), ['file' => $file])->assertSessionHasErrors(['file' => 'The workbook is too large when expanded or contains macros. Save a values-only workbook.']);
        }
        $this->assertDatabaseCount('senior_citizens', 0);
    }

    public function test_csv_header_aliases_quotes_and_blank_rows_are_supported(): void
    {
        $this->actingAs(User::factory()->create());
        $row = [...$this->row(), 'address' => "Synthetic, street\nSecond line"];
        $file = $this->file([array_fill_keys(SeniorImportReader::COLUMNS, ''), $row], 'csv');
        $content = file_get_contents($file->getPathname());
        $content = str_replace(['first_name', 'barangay', 'contact_number'], ['First Name', 'Barangay Code', 'Mobile Number'], $content);
        $response = $this->post(route('registrations.import.preview'), ['file' => UploadedFile::fake()->createWithContent('aliases.csv', $content)])->assertSessionHasNoErrors()->assertRedirect();
        $token = basename(parse_url($response->headers->get('Location'), PHP_URL_PATH));
        $this->post(route('registrations.import.store', $token), ['reviewed' => 1])->assertRedirect();

        $this->assertSame("SYNTHETIC, STREET\nSECOND LINE", SeniorCitizen::sole()->address);
        $this->assertDatabaseCount('senior_citizens', 1);
    }

    private function row(): array
    {
        Barangay::firstOrCreate(['code' => 'SYN'], ['name' => 'Synthetic Barangay']);

        return array_replace(array_fill_keys(SeniorImportReader::COLUMNS, ''), ['first_name' => 'synthetic ñandú', 'last_name' => 'Applicant', 'name_suffix' => 'jr.', 'birth_date' => '01/02/1950', 'sex' => 'F', 'civil_status' => 'widowed', 'barangay' => 'syn', 'address' => 'Synthetic address', 'contact_number' => '0967 234 8976', 'osca_id_number' => '000123']);
    }

    private function preview(array $rows, string $format = 'csv', ?callable $configure = null): string
    {
        $response = $this->post(route('registrations.import.preview'), ['file' => $this->file($rows, $format, $configure)])->assertSessionHasNoErrors()->assertRedirect();

        return basename(parse_url($response->headers->get('Location'), PHP_URL_PATH));
    }

    private function file(array $rows, string $format, ?callable $configure = null): UploadedFile
    {
        if ($format === 'csv') {
            $stream = fopen('php://temp', 'w+');
            fwrite($stream, "\xEF\xBB\xBF");
            foreach ([SeniorImportReader::COLUMNS, ...array_map(fn ($row) => array_values($row), $rows)] as $row) {
                fputcsv($stream, $row, ',', '"', '');
            }
            rewind($stream);
            $content = stream_get_contents($stream);
            fclose($stream);
        } else {
            $book = new Spreadsheet;
            $sheet = $book->getActiveSheet();
            foreach ([SeniorImportReader::COLUMNS, ...array_map(fn ($row) => array_values($row), $rows)] as $index => $row) {
                foreach ($row as $column => $value) {
                    $sheet->setCellValueExplicit([$column + 1, $index + 1], $value, is_numeric($value) && ! is_string($value) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING);
                }
            }
            $configure?->__invoke($book);
            ob_start();
            try {
                IOFactory::createWriter($book, $format === 'xlsx' ? 'Xlsx' : 'Xls')->save('php://output');
                $content = ob_get_contents();
            } finally {
                ob_end_clean();
                $book->disconnectWorksheets();
            }
        }

        return UploadedFile::fake()->createWithContent('synthetic-seniors.'.$format, $content);
    }
}
