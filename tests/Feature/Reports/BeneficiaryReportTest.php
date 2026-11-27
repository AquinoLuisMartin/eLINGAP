<?php

namespace Tests\Feature\Reports;

use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\Beneficiary;
use App\Models\BenefitEntitlement;
use App\Models\BenefitPayment;
use App\Models\BenefitSchedule;
use App\Models\Payout;
use App\Models\PayoutSchedule;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Models\User;
use App\Services\Reports\StaffReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class BeneficiaryReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_benefit_records_and_claims_feed_the_report_and_filtered_downloads(): void
    {
        $this->travelTo('2026-11-21 14:00:00');
        $this->actingAs($staff = User::factory()->create());
        $area = Barangay::create(['name' => 'Synthetic North', 'code' => 'N']);
        $program = Program::create(['name' => 'Synthetic cash program', 'agency' => 'Test']);
        $senior = $this->senior($area, 'Synthetic recipient');
        $schedule = BenefitSchedule::create([
            'program_id' => $program->id, 'reference_number' => 'SYNTHETIC-SCHEDULE', 'period_key' => '2026',
            'eligibility_on' => '2026-01-01', 'scheduled_at' => now()->subDay(), 'location' => 'Synthetic venue',
            'amount' => 500, 'available_funds' => 1000, 'barangay_ids' => [], 'rule_snapshot' => [], 'status' => 'APPROVED', 'created_by' => $staff->id,
        ]);
        $claimed = BenefitEntitlement::create([
            'senior_citizen_id' => $senior->id, 'program_id' => $program->id, 'period_key' => '2026',
            'eligibility_on' => '2026-01-01', 'amount' => 500, 'status' => 'CLAIMED',
            'benefit_schedule_id' => $schedule->id, 'rule_snapshot' => [],
        ]);
        $pending = BenefitEntitlement::create([
            'senior_citizen_id' => $senior->id, 'program_id' => $program->id, 'period_key' => '2025',
            'eligibility_on' => '2025-01-01', 'status' => 'PENDING', 'rule_snapshot' => [],
        ]);
        BenefitPayment::create([
            'benefit_entitlement_id' => $claimed->id, 'benefit_schedule_id' => $schedule->id,
            'reference_number' => 'SYNTHETIC-CLAIM', 'amount' => 500, 'claimant_type' => 'REPRESENTATIVE',
            'claimant_name' => 'Synthetic representative', 'recorded_by' => $staff->id, 'paid_at' => now(),
        ]);
        $legacySchedule = PayoutSchedule::create(['program_id' => $program->id, 'reference_number' => 'SYNTHETIC-LEGACY', 'scheduled_on' => '2025-11-21', 'amount' => 300]);
        Payout::create(['payout_schedule_id' => $legacySchedule->id, 'senior_citizen_id' => $senior->id, 'amount' => 300, 'status' => 'RELEASED', 'claimant_type' => 'SELF', 'claimant_name' => 'Synthetic legacy claimant']);

        $all = $this->get(route('reports.workspace', ['type' => 'payouts']))->assertOk();
        $this->assertSame(3, $all->viewData('records')->total());
        $this->assertSame(['benefit' => 2, 'legacy' => 1], $all->viewData('records')->getCollection()->countBy('source')->all());
        $filters = ['type' => 'payouts', 'status' => 'CLAIMED', 'program_id' => $program->id, 'barangay_id' => $area->id, 'from' => '2026-11-21', 'to' => '2026-11-21'];
        $preview = $this->get(route('reports.workspace', $filters))->assertOk()->assertSee('Synthetic representative');
        $this->assertSame([$claimed->id], $preview->viewData('records')->pluck('id')->all());
        $csv = $this->get(route('reports.export', $filters))->assertOk()->streamedContent();
        $this->assertStringContainsString('Synthetic representative', $csv);
        $this->assertSame(1, $this->get(route('reports.workspace', ['type' => 'proxy']))->assertOk()->viewData('records')->total());
        $this->assertSame([$pending->id], $this->get(route('reports.workspace', ['type' => 'payouts', 'status' => 'PENDING']))->assertOk()->viewData('records')->pluck('id')->all());
        $this->get(route('reports.export', [...$filters, 'format' => 'xlsx']))->assertOk();
        $this->get(route('reports.export', [...$filters, 'format' => 'pdf']))->assertOk();
    }

    public function test_beneficiary_preview_and_csv_filter_program_barangay_age_and_active_verified_assignments(): void
    {
        $this->travelTo('2026-11-11 10:30:00');
        $barangay = Barangay::create(['name' => 'Synthetic North', 'code' => 'N']);
        $other = Barangay::create(['name' => 'Synthetic South', 'code' => 'S']);
        $program = Program::create(['name' => 'Synthetic assistance', 'agency' => 'Test', 'status' => 'ACTIVE']);
        $different = Program::create(['name' => 'Other assistance', 'agency' => 'Test', 'status' => 'ACTIVE']);
        $included = [];
        foreach ([
            ['Minimum', '1966-11-11', $barangay, $program, 'VERIFIED', 'ACTIVE'],
            ['Maximum', '1961-11-12', $barangay, $program, 'VERIFIED', 'ACTIVE'],
            ['Young', '1966-11-12', $barangay, $program, 'VERIFIED', 'ACTIVE'],
            ['Old', '1961-11-11', $barangay, $program, 'VERIFIED', 'ACTIVE'],
            ['Area', '1964-01-01', $other, $program, 'VERIFIED', 'ACTIVE'],
            ['Program', '1964-01-01', $barangay, $different, 'VERIFIED', 'ACTIVE'],
            ['Deceased', '1964-01-01', $barangay, $program, 'DECEASED', 'ACTIVE'],
            ['Inactive', '1964-01-01', $barangay, $program, 'VERIFIED', 'INACTIVE'],
        ] as [$name, $birthDate, $area, $benefit, $seniorStatus, $assignmentStatus]) {
            $senior = $this->senior($area, $name, $birthDate, $seniorStatus);
            $assignment = Beneficiary::create(['senior_citizen_id' => $senior->id, 'program_id' => $benefit->id, 'enrolled_on' => '2026-01-01', 'status' => $assignmentStatus]);
            if (in_array($name, ['Minimum', 'Maximum'], true)) {
                $included[] = $assignment->id;
            }
        }
        $this->actingAs(User::factory()->create(['first_name' => 'Synthetic', 'middle_name' => 'Report', 'last_name' => 'Exporter', 'name_suffix' => null]));
        $filters = ['type' => 'beneficiaries', 'program_id' => $program->id, 'barangay_id' => $barangay->id, 'age_min' => 60, 'age_max' => 64];

        $preview = $this->get(route('reports.workspace', $filters))->assertOk();
        $csv = $this->get(route('reports.export', $filters))->assertOk()
            ->assertDownload('Synthetic North - Synthetic assistance - Program beneficiaries - 2026-11-11.csv')->streamedContent();

        $this->assertSame(array_reverse($included), $preview->viewData('records')->pluck('id')->all());
        $rows = $this->csvRows($csv);
        $this->assertSame(['OSCA ID', 'Last name', 'First name', 'Middle name', 'Suffix', 'Age', 'Barangay', 'Program', 'Enrolled on', 'Assignment status'], $rows[0]);
        $this->assertSame(['Maximum', 'Minimum'], [$rows[1][1], $rows[2][1]]);
        $this->assertSame(['Synthetic', 'Middle', '', '60', 'Synthetic North', 'Synthetic assistance', '2026-01-01', 'ACTIVE'], array_slice($rows[2], 2));
        $this->assertSame([null], $rows[3]);
        $this->assertStringContainsString('2026-11-11 10:30:00 Asia/Manila', $csv);
        $this->assertContains(['Generated by', 'Synthetic Report Exporter'], $rows);
        $this->assertDatabaseHas('audit_logs', ['action' => 'report.exported', 'user_id' => auth()->id()]);
    }

    public function test_population_counts_only_verified_seniors_and_includes_zero_population_barangays(): void
    {
        $this->travelTo('2026-11-11 10:30:00');
        $first = Barangay::create(['name' => 'Synthetic North', 'code' => 'N']);
        $empty = Barangay::create(['name' => 'Synthetic South', 'code' => 'S']);
        foreach (['VERIFIED', 'VERIFIED', 'PENDING', 'DECEASED', 'ARCHIVED', 'TRANSFERRED'] as $index => $status) {
            $this->senior($first, 'Record'.$index, '1950-01-01', $status);
        }
        $this->actingAs(User::factory()->create());

        $preview = $this->get(route('reports.workspace', ['type' => 'population']))->assertOk();
        $csv = $this->get(route('reports.export', ['type' => 'population']))->assertOk()->streamedContent();

        $this->assertSame([$first->id => 2, $empty->id => 0], $preview->viewData('population')->mapWithKeys(fn ($area) => [$area->id => (int) $area->population])->all());
        $rows = $this->csvRows($csv);
        $this->assertSame(['Synthetic North', '2'], $rows[1]);
        $this->assertSame(['Synthetic South', '0'], $rows[2]);
        $this->assertSame(['Total verified senior population', '2'], $rows[array_key_last($rows)]);
        $this->assertStringContainsString('2026-11-11 10:30:00 Asia/Manila', $csv);
    }

    public function test_population_total_remains_municipality_wide_when_report_filters_select_one_barangay(): void
    {
        $first = Barangay::create(['name' => 'Synthetic North', 'code' => 'N']);
        $second = Barangay::create(['name' => 'Synthetic South', 'code' => 'S']);
        $this->senior($first, 'North');
        $this->senior($second, 'South');
        $this->actingAs(User::factory()->create());

        $preview = $this->get(route('reports.workspace', ['type' => 'population', 'barangay_id' => $first->id]))->assertOk();

        $this->assertSame(1, $preview->viewData('records')->total());
        $this->assertSame(2, $preview->viewData('population')->sum('population'));
    }

    #[TestWith(['age_min', '59'])]
    #[TestWith(['age_min', '151'])]
    #[TestWith(['age_min', '60.5'])]
    #[TestWith(['age_max', '59'])]
    #[TestWith(['age_max', '151'])]
    #[TestWith(['age_max', 'invalid'])]
    #[TestWith(['program_id', '999999'])]
    #[TestWith(['barangay_id', '999999'])]
    public function test_invalid_filters_return_422_in_preview_and_export(string $field, string $value): void
    {
        $this->actingAs(User::factory()->create());
        $filters = ['type' => 'beneficiaries', $field => $value];

        $this->getJson(route('reports.workspace', $filters))->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->getJson(route('reports.export', $filters))->assertUnprocessable()->assertJsonValidationErrors($field);
    }

    public function test_reversed_age_range_and_unknown_export_format_return_422(): void
    {
        $this->actingAs(User::factory()->create());

        $this->getJson(route('reports.workspace', ['type' => 'beneficiaries', 'age_min' => 80, 'age_max' => 70]))
            ->assertUnprocessable()->assertJsonValidationErrors('age_max');
        $this->getJson(route('reports.export', ['type' => 'beneficiaries', 'format' => 'html']))
            ->assertUnprocessable()->assertJsonValidationErrors('format');
    }

    public function test_csv_escapes_separate_names_program_names_and_population_labels(): void
    {
        $barangay = Barangay::create(['name' => '=Area', 'code' => 'N']);
        $program = Program::create(['name' => '=Program', 'agency' => 'Test']);
        $senior = $this->senior($barangay, '=Last');
        $senior->update(['first_name' => '+First', 'middle_name' => '@Middle']);
        Beneficiary::create(['senior_citizen_id' => $senior->id, 'program_id' => $program->id, 'enrolled_on' => '2026-01-01']);
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('reports.export', ['type' => 'beneficiaries', 'program_id' => $program->id]))->assertOk();
        $rows = $this->csvRows($response->streamedContent());

        $this->assertSame(["'=Last", "'+First", "'@Middle"], array_slice($rows[1], 1, 3));
        $this->assertSame("'=Program", $rows[1][7]);
        $this->assertContains(["'=Area", '1'], $rows);
        $this->assertContains(['Program', "'=Program"], $rows);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    #[TestWith(['masterlist'])]
    #[TestWith(['beneficiaries'])]
    #[TestWith(['population'])]
    #[TestWith(['applications'])]
    #[TestWith(['payouts'])]
    #[TestWith(['milestones'])]
    #[TestWith(['proxy'])]
    #[TestWith(['archived'])]
    public function test_each_report_downloads_a_real_pdf_including_an_empty_report(string $type): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('reports.export', ['type' => $type, 'format' => 'pdf']))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('attachment;', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'report.exported', 'user_id' => auth()->id()]);
    }

    public function test_pdf_downloads_a_populated_beneficiary_report(): void
    {
        $barangay = Barangay::create(['name' => 'Synthetic North', 'code' => 'N']);
        $program = Program::create(['name' => 'Synthetic program', 'agency' => 'Test']);
        $senior = $this->senior($barangay, 'Beneficiary');
        Beneficiary::create(['senior_citizen_id' => $senior->id, 'program_id' => $program->id, 'enrolled_on' => '2026-01-01']);
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('reports.export', ['type' => 'beneficiaries', 'format' => 'pdf', 'program_id' => $program->id]))->assertOk();

        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_population_pdf_embeds_system_fonts_and_the_logo(): void
    {
        Barangay::create(['name' => 'Synthetic North', 'code' => 'N']);
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('reports.export', ['type' => 'population', 'format' => 'pdf']))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringContainsString('Inter-Regular', $response->getContent());
        $this->assertStringContainsString('Inter-Bold', $response->getContent());
        $this->assertStringContainsString('/Subtype /Image', $response->getContent());
    }

    public function test_beneficiary_pdf_template_omits_the_municipality_wide_population(): void
    {
        $report = new StaffReport;

        $html = view('reports.pdf', [
            'title' => StaffReport::TYPES['beneficiaries'], 'type' => 'beneficiaries', 'report' => $report,
            'headings' => $report->headings('beneficiaries'), 'records' => collect(),
            'generatedAt' => now(), 'generatedBy' => 'Synthetic Exporter', 'filterLabels' => ['Program' => 'Synthetic program'], 'total' => 0,
        ])->render();

        $this->assertStringContainsString('Synthetic program', $html);
        $this->assertStringContainsString('Assignment status', $html);
        $this->assertStringNotContainsString('Municipality-wide verified population', $html);
    }

    public function test_guests_cannot_preview_or_download_reports(): void
    {
        $this->get(route('reports.workspace', ['type' => 'beneficiaries']))->assertRedirectToRoute('login');
        $this->get(route('reports.export', ['type' => 'beneficiaries', 'format' => 'pdf']))->assertRedirectToRoute('login');
    }

    public function test_application_csv_includes_osca_id_separate_names_and_application_columns(): void
    {
        $barangay = Barangay::create(['name' => 'Synthetic North', 'code' => 'N']);
        $senior = $this->senior($barangay, 'Applicant');
        $senior->update(['osca_id_number' => 'SYN-001']);
        $program = Program::create(['name' => 'Synthetic program', 'agency' => 'Test']);
        Application::create(['senior_citizen_id' => $senior->id, 'program_id' => $program->id, 'application_number' => 'SYN-APP-001', 'applied_on' => '2026-11-11', 'status' => 'PENDING']);
        $this->actingAs(User::factory()->create());

        $csv = $this->get(route('reports.export', ['type' => 'applications']))->assertOk()->streamedContent();
        $this->get(route('reports.export', ['type' => 'applications', 'format' => 'pdf']))->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $this->assertSame(['SYN-001', 'Applicant', 'Synthetic', 'Middle', '', 'SYN-APP-001', 'Synthetic North', 'Synthetic program', '2026-11-11', 'PENDING', 'Not recorded', 'Not recorded', ''], $this->csvRows($csv)[1]);
    }

    #[TestWith(['payouts'])]
    #[TestWith(['proxy'])]
    public function test_payout_csv_includes_separate_names_and_report_specific_columns(string $type): void
    {
        $barangay = Barangay::create(['name' => 'Synthetic North', 'code' => 'N']);
        $senior = $this->senior($barangay, 'Recipient');
        $senior->update(['osca_id_number' => 'SYN-001']);
        $program = Program::create(['name' => 'Synthetic program', 'agency' => 'Test']);
        $schedule = PayoutSchedule::create(['program_id' => $program->id, 'reference_number' => 'SYN-PAY-001', 'scheduled_on' => '2026-11-11', 'amount' => '1000.00']);
        Payout::create(['payout_schedule_id' => $schedule->id, 'senior_citizen_id' => $senior->id, 'amount' => '1000.00', 'status' => 'RELEASED', 'claimant_type' => 'PROXY', 'claimant_name' => 'Synthetic proxy']);
        $this->actingAs(User::factory()->create());

        $csv = $this->get(route('reports.export', ['type' => $type]))->assertOk()->streamedContent();
        $this->get(route('reports.export', ['type' => $type, 'format' => 'pdf']))->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $this->assertSame(['SYN-001', 'Recipient', 'Synthetic', 'Middle', '', 'Synthetic North', 'Synthetic program', '1000.00', 'Synthetic proxy', 'RELEASED'], $this->csvRows($csv)[1]);
    }

    public function test_csv_exports_every_matching_record_across_chunks_and_pdf_rejects_oversized_reports(): void
    {
        $this->travelTo('2026-11-11 10:30:00');
        $barangay = Barangay::create(['name' => 'Synthetic North', 'code' => 'N']);
        foreach (array_chunk(range(1, 2001), 400) as $chunk) {
            SeniorCitizen::insert(array_map(fn ($index) => [
                'barangay_id' => $barangay->id, 'first_name' => 'Synthetic', 'last_name' => 'Record'.str_pad((string) (2002 - $index), 4, '0', STR_PAD_LEFT),
                'birth_date' => '1966-11-11', 'sex' => 'FEMALE', 'address' => 'Test address', 'status' => 'VERIFIED',
            ], $chunk));
        }
        $this->actingAs(User::factory()->create());

        $csv = $this->get(route('reports.export', ['type' => 'masterlist', 'age_max' => 60]))->assertOk()->streamedContent();
        $rows = $this->csvRows($csv);
        $this->getJson(route('reports.export', ['type' => 'masterlist', 'format' => 'pdf']))
            ->assertUnprocessable()->assertJsonValidationErrors('format');

        $this->assertSame(['Record0001', 'Record2001'], [$rows[1][1], $rows[2001][1]]);
        $this->assertSame(2001, count(array_unique(array_column(array_slice($rows, 1, 2001), 1))));
        $this->assertSame([null], $rows[2002]);
        $this->assertSame(1, AuditLog::where('action', 'report.exported')->count());
    }

    #[TestWith(['age_min', 61, ['Older']])]
    #[TestWith(['age_max', 60, ['Younger']])]
    public function test_masterlist_accepts_either_age_boundary_on_its_own(string $field, int $age, array $expected): void
    {
        $this->travelTo('2026-11-11 10:30:00');
        $barangay = Barangay::create(['name' => 'Synthetic North', 'code' => 'N']);
        $this->senior($barangay, 'Younger', '1966-11-11');
        $this->senior($barangay, 'Older', '1965-11-11');
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('reports.workspace', ['type' => 'masterlist', $field => $age]))->assertOk();

        $this->assertSame($expected, $response->viewData('records')->pluck('last_name')->all());
    }

    public function test_report_pdf_template_escapes_names_and_filter_labels(): void
    {
        $this->travelTo('2026-11-11 10:30:00');
        $barangay = (new Barangay)->forceFill(['name' => '<script>area</script>']);
        $senior = (new SeniorCitizen)->forceFill([
            'last_name' => '<script>name</script>', 'first_name' => 'Synthetic', 'birth_date' => '1966-11-11', 'status' => 'VERIFIED',
        ])->setRelation('barangay', $barangay);

        $html = view('reports.pdf', [
            'title' => 'Senior citizen masterlist', 'type' => 'masterlist', 'report' => new StaffReport,
            'headings' => (new StaffReport)->headings('masterlist'), 'records' => collect([$senior]),
            'generatedAt' => now(), 'generatedBy' => 'Synthetic <Exporter>', 'filterLabels' => ['Barangay' => '<script>area</script>'], 'total' => 1,
        ])->render();

        $this->assertStringContainsString('&lt;script&gt;name&lt;/script&gt;', $html);
        $this->assertStringContainsString('&lt;script&gt;area&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('eLINGAP', $html);
        $this->assertMatchesRegularExpression('~<footer>\s*<p>Generated: November 11, 2026, 10:30:00 AM Asia/Manila</p>\s*<p>Generated by: Synthetic &lt;Exporter&gt;</p>\s*</footer>~', $html);
        $this->assertStringNotContainsString('<Exporter>', $html);
        $this->assertSame(1, substr_count($html, 'November 11, 2026, 10:30:00 AM Asia/Manila'));
    }

    private function senior(Barangay $barangay, string $lastName, string $birthDate = '1950-01-01', string $status = 'VERIFIED'): SeniorCitizen
    {
        return SeniorCitizen::create([
            'barangay_id' => $barangay->id, 'first_name' => 'Synthetic', 'middle_name' => 'Middle', 'last_name' => $lastName,
            'birth_date' => $birthDate, 'sex' => 'FEMALE', 'address' => 'Test address', 'status' => $status,
        ]);
    }

    private function csvRows(string $csv): array
    {
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, $csv);
        rewind($stream);
        $rows = [];
        while (($row = fgetcsv($stream, escape: '')) !== false) {
            $rows[] = $row;
        }
        fclose($stream);

        return $rows;
    }
}
