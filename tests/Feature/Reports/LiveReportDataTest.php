<?php

namespace Tests\Feature\Reports;

use App\Models\Application;
use App\Models\Barangay;
use App\Models\Beneficiary;
use App\Models\BenefitEntitlement;
use App\Models\BenefitPayment;
use App\Models\BenefitSchedule;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Models\User;
use App\Services\Benefits\BenefitAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class LiveReportDataTest extends TestCase
{
    use RefreshDatabase;

    public static function reportFormats(): iterable
    {
        foreach (['masterlist', 'beneficiaries', 'population', 'applications', 'payouts', 'milestones', 'proxy', 'archived'] as $type) {
            foreach (['csv', 'xlsx', 'pdf'] as $format) {
                yield $type.' '.$format => [$type, $format];
            }
        }
    }

    #[DataProvider('reportFormats')]
    public function test_each_report_filters_live_records_consistently_in_preview_and_download(string $type, string $format): void
    {
        $this->travelTo('2026-11-21 14:00:00');
        $this->actingAs($staff = User::factory()->create());
        $north = Barangay::create(['name' => 'Synthetic North', 'code' => 'N']);
        $south = Barangay::create(['name' => 'Synthetic South', 'code' => 'S']);
        $program = Program::create(['name' => 'Synthetic program', 'agency' => 'Test', 'status' => 'ACTIVE', 'benefit_rules' => ['milestones' => [80 => 1000, 90 => 2000]]]);
        $included = $this->senior($north, 'MATCH-SYN', $type === 'archived' ? 'ARCHIVED' : 'VERIFIED');
        $excluded = $this->senior($south, 'OTHER-SYN');
        foreach ([$included, $excluded] as $senior) {
            $senior->beneficiaries()->create(['program_id' => $program->id, 'enrolled_on' => '2026-01-01', 'status' => 'ACTIVE']);
        }
        $filters = ['type' => $type, 'barangay_id' => $north->id, 'search' => 'MATCH-SYN'];
        if ($type !== 'archived') {
            $filters['program_id'] = $program->id;
        }
        if (in_array($type, ['masterlist', 'beneficiaries', 'population', 'milestones', 'archived'])) {
            $filters += ['age_min' => 80, 'age_max' => 89];
        }
        if ($type === 'masterlist' || $type === 'archived') {
            $filters['status'] = $included->status->value;
        }
        if ($type === 'milestones') {
            $filters['milestone'] = 80;
        }
        if ($type === 'applications') {
            foreach ([$included, $excluded] as $senior) {
                Application::create(['senior_citizen_id' => $senior->id, 'program_id' => $program->id, 'application_number' => 'APP-'.$senior->id, 'applied_on' => today(), 'status' => 'PENDING']);
            }
            $filters['status'] = 'PENDING';
        }
        if (in_array($type, ['payouts', 'proxy'])) {
            $schedule = BenefitSchedule::create([
                'program_id' => $program->id, 'reference_number' => 'SYN-SCHEDULE', 'period_key' => '2026',
                'eligibility_on' => today(), 'scheduled_at' => now()->subDay(), 'location' => 'Synthetic venue',
                'barangay_ids' => [], 'amount' => 500, 'available_funds' => 1000, 'rule_snapshot' => [], 'status' => 'APPROVED', 'created_by' => $staff->id,
            ]);
            foreach ([$included, $excluded] as $senior) {
                $entry = BenefitEntitlement::create(['senior_citizen_id' => $senior->id, 'program_id' => $program->id, 'period_key' => '2026', 'eligibility_on' => today(), 'status' => 'CLAIMED', 'amount' => 600, 'benefit_schedule_id' => $schedule->id, 'rule_snapshot' => []]);
                BenefitPayment::create(['benefit_entitlement_id' => $entry->id, 'benefit_schedule_id' => $schedule->id, 'reference_number' => 'SYN-PAY-'.$senior->id, 'amount' => 500, 'claimant_type' => $type === 'proxy' ? 'REPRESENTATIVE' : 'SELF', 'claimant_name' => 'Synthetic claimant', 'recorded_by' => $staff->id, 'paid_at' => now()]);
            }
            if ($type === 'payouts') {
                $filters['status'] = 'CLAIMED';
            }
        }
        if (in_array($type, ['applications', 'payouts', 'proxy'])) {
            $filters += ['from' => '2026-11-21', 'to' => '2026-11-21'];
        }

        $preview = $this->get(route('reports.workspace', $filters))->assertOk();
        $this->assertSame(1, $preview->viewData('records')->total());
        if ($type === 'population') {
            $this->assertSame(['Matching verified population' => 1], $preview->viewData('summary'));
        } elseif (in_array($type, ['payouts', 'proxy'])) {
            $this->assertSame('₱500.00', $preview->viewData('summary')['Claimed amount']);
            $this->assertSame(1, $preview->viewData('summary')['Claimed records']);
        }
        $download = $this->get(route('reports.export', [...$filters, 'format' => $format]))->assertOk();
        $marker = $type === 'population' ? $north->name : $included->osca_id_number;
        if ($format === 'pdf') {
            $pdf = $download->getContent();
            $this->assertStringStartsWith('%PDF-', $pdf);
            preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $streams);
            $text = implode('', array_map(fn ($stream) => @gzuncompress($stream) ?: $stream, $streams[1]));
            $this->assertStringContainsString(mb_convert_encoding($marker, 'UTF-16BE', 'UTF-8'), $text);
            $this->assertStringNotContainsString(mb_convert_encoding($excluded->osca_id_number, 'UTF-16BE', 'UTF-8'), $text);
        } elseif ($format === 'xlsx') {
            $path = tempnam(storage_path('framework/testing'), 'live-report-');
            try {
                file_put_contents($path, $download->streamedContent());
                $book = IOFactory::load($path);
                $records = $book->getSheetByName('Records');
                $this->assertSame(2, $records->getHighestDataRow());
                $this->assertSame($marker, $records->getCell('A2')->getValue());
                $details = array_column($book->getSheetByName('Report details')->toArray(), 1, 0);
                foreach ($preview->viewData('summary') as $label => $value) {
                    $this->assertSame((string) $value, (string) $details[$label]);
                }
            } finally {
                if (isset($book)) {
                    $book->disconnectWorksheets();
                }
                unlink($path);
            }
        } else {
            $rows = $this->csvRows($download->streamedContent());
            $this->assertSame($marker, $rows[1][0]);
            $this->assertSame([null], $rows[2]);
            foreach ($preview->viewData('summary') as $label => $value) {
                $this->assertContains([$label, (string) $value], $rows);
            }
        }
    }

    public function test_beneficiary_report_requires_staff_selection_and_reflects_saved_resolution(): void
    {
        $this->actingAs(User::factory()->create());
        $area = Barangay::create(['name' => 'Synthetic North', 'code' => 'N']);
        $senior = $this->senior($area, 'CONFLICT-SYN');
        $programs = collect(['First', 'Second'])->map(fn ($name) => Program::create(['name' => 'Synthetic '.$name, 'agency' => 'Test', 'status' => 'ACTIVE']));
        foreach ($programs as $program) {
            $senior->beneficiaries()->create(['program_id' => $program->id, 'enrolled_on' => today(), 'status' => 'ACTIVE']);
        }

        $preview = $this->get(route('reports.workspace', ['type' => 'beneficiaries']))->assertOk();
        $this->assertSame(1, $preview->viewData('records')->total());
        $this->assertSame(['Confirmed assignments' => 0, 'Program selection required' => 1], $preview->viewData('summary'));
        $row = $preview->viewData('report')->row('beneficiaries', $preview->viewData('records')->first());
        $this->assertSame('Program selection required', $row[7]);
        $this->assertSame(0, $this->get(route('reports.workspace', ['type' => 'beneficiaries', 'program_id' => $programs->first()->id]))->viewData('records')->total());

        app(BenefitAssignment::class)->resolve($senior, $programs->last());

        $resolved = $this->get(route('reports.workspace', ['type' => 'beneficiaries', 'program_id' => $programs->last()->id]))->assertOk();
        $this->assertSame(1, $resolved->viewData('records')->total());
        $this->assertSame(['Confirmed assignments' => 1, 'Program selection required' => 0], $resolved->viewData('summary'));
        $this->assertDatabaseCount('beneficiaries', 2);
        $this->assertSame(1, Beneficiary::where('status', 'INACTIVE')->count());
    }

    public function test_application_log_uses_saved_reviews_and_never_treats_submission_as_review(): void
    {
        $this->travelTo('2026-11-21 14:00:00');
        $this->actingAs($staff = User::factory()->create());
        $area = Barangay::create(['name' => 'Synthetic North', 'code' => 'N']);
        $senior = $this->senior($area, 'APP-SYN');
        $program = Program::create(['name' => 'Synthetic program', 'agency' => 'Test']);
        $application = Application::create(['senior_citizen_id' => $senior->id, 'program_id' => $program->id, 'application_number' => 'APP-REVIEW-SYN', 'applied_on' => today(), 'status' => 'PENDING']);
        $application->statusHistories()->create(['to_status' => 'PENDING', 'changed_by' => $staff->id]);

        $pending = $this->get(route('reports.workspace', ['type' => 'applications', 'search' => 'APP-REVIEW-SYN']))->assertOk();
        $row = $pending->viewData('report')->row('applications', $pending->viewData('records')->first());
        $this->assertSame(['Not recorded', 'Not recorded', ''], array_slice($row, 10));

        $application->update(['status' => 'APPROVED']);
        $application->statusHistories()->create(['from_status' => 'PENDING', 'to_status' => 'APPROVED', 'remarks' => 'Synthetic eligibility reviewed', 'changed_by' => $staff->id]);
        $approved = $this->get(route('reports.workspace', ['type' => 'applications', 'status' => 'APPROVED', 'program_id' => $program->id]))->assertOk();
        $row = $approved->viewData('report')->row('applications', $approved->viewData('records')->first());
        $this->assertSame(['Nov 21, 2026 2:00 PM', $staff->full_name, 'Synthetic eligibility reviewed'], array_slice($row, 10));
    }

    public function test_payout_totals_and_exports_cover_all_matching_pages_and_keep_each_payout(): void
    {
        $this->actingAs(User::factory()->create());
        $area = Barangay::create(['name' => 'Synthetic North', 'code' => 'N']);
        $senior = $this->senior($area, 'PAGED-SYN');
        $program = Program::create(['name' => 'Synthetic program', 'agency' => 'Test']);
        for ($period = 1; $period <= 21; $period++) {
            BenefitEntitlement::create(['senior_citizen_id' => $senior->id, 'program_id' => $program->id, 'period_key' => 'SYN-'.$period, 'eligibility_on' => today(), 'amount' => 100, 'status' => 'PENDING', 'rule_snapshot' => []]);
        }
        $filters = ['type' => 'payouts', 'program_id' => $program->id, 'status' => 'PENDING', 'search' => 'PAGED-SYN'];

        $preview = $this->get(route('reports.workspace', [...$filters, 'page' => 2]))->assertOk();
        $this->assertCount(1, $preview->viewData('records'));
        $this->assertSame(21, $preview->viewData('records')->total());
        $this->assertSame('₱2,100.00', $preview->viewData('summary')['Payout amount']);
        $this->assertSame(21, $preview->viewData('summary')['Pending / unclaimed records']);
        $rows = $this->csvRows($this->get(route('reports.export', [...$filters, 'page' => 2]))->assertOk()->streamedContent());
        $this->assertCount(21, array_filter($rows, fn ($row) => $row[0] === 'PAGED-SYN'));
        $this->assertContains(['Pending / unclaimed records', '21'], $rows);
    }

    public function test_proxy_report_excludes_personal_and_reversed_claims_without_duplicate_counts(): void
    {
        $this->actingAs($staff = User::factory()->create());
        $area = Barangay::create(['name' => 'Synthetic North', 'code' => 'N']);
        $senior = $this->senior($area, 'PROXY-SYN');
        $program = Program::create(['name' => 'Synthetic program', 'agency' => 'Test']);
        $schedule = BenefitSchedule::create(['program_id' => $program->id, 'reference_number' => 'SYN-SCHEDULE', 'period_key' => '2026', 'eligibility_on' => today(), 'scheduled_at' => now(), 'location' => 'Synthetic venue', 'barangay_ids' => [], 'rule_snapshot' => [], 'status' => 'APPROVED', 'created_by' => $staff->id]);
        foreach (['SELF', 'REPRESENTATIVE', 'REVERSED'] as $index => $method) {
            $entry = BenefitEntitlement::create(['senior_citizen_id' => $senior->id, 'program_id' => $program->id, 'period_key' => 'SYN-'.$index, 'eligibility_on' => today(), 'amount' => 500, 'status' => 'CLAIMED', 'benefit_schedule_id' => $schedule->id, 'rule_snapshot' => []]);
            BenefitPayment::create(['benefit_entitlement_id' => $entry->id, 'benefit_schedule_id' => $schedule->id, 'reference_number' => 'SYN-CLAIM-'.$index, 'amount' => 500, 'claimant_type' => $method === 'SELF' ? 'SELF' : 'REPRESENTATIVE', 'claimant_name' => 'Synthetic claimant', 'paid_at' => now(), 'recorded_by' => $staff->id, 'reversed_at' => $method === 'REVERSED' ? now() : null]);
        }

        $preview = $this->get(route('reports.workspace', ['type' => 'proxy', 'program_id' => $program->id]))->assertOk();

        $this->assertSame(1, $preview->viewData('records')->total());
        $this->assertSame(1, $preview->viewData('summary')['Claimed records']);
        $this->assertSame('₱500.00', $preview->viewData('summary')['Claimed amount']);
    }

    #[TestWith(['masterlist'])]
    #[TestWith(['applications'])]
    #[TestWith(['payouts'])]
    public function test_zero_is_a_real_search_term_instead_of_an_empty_filter(string $type): void
    {
        $this->actingAs(User::factory()->create());
        $area = Barangay::create(['name' => 'Synthetic North', 'code' => 'N']);
        $program = Program::create(['name' => 'Synthetic program', 'agency' => 'Test']);
        foreach (['MATCH-0', 'OTHER-1'] as $id) {
            $senior = $this->senior($area, $id);
            if ($type === 'applications') {
                Application::create(['senior_citizen_id' => $senior->id, 'program_id' => $program->id, 'application_number' => 'APP-'.$id, 'applied_on' => today(), 'status' => 'PENDING']);
            } elseif ($type === 'payouts') {
                BenefitEntitlement::create(['senior_citizen_id' => $senior->id, 'program_id' => $program->id, 'period_key' => '2026', 'eligibility_on' => today(), 'status' => 'PENDING', 'rule_snapshot' => []]);
            }
        }

        $preview = $this->get(route('reports.workspace', ['type' => $type, 'search' => '0']))->assertOk();

        $this->assertSame(1, $preview->viewData('records')->total());
        $this->assertSame('MATCH-0', $preview->viewData('report')->row($type, $preview->viewData('records')->first())[0]);
    }

    private function senior(Barangay $area, string $id, string $status = 'VERIFIED'): SeniorCitizen
    {
        return SeniorCitizen::unguarded(fn () => SeniorCitizen::create(['barangay_id' => $area->id, 'osca_id_number' => $id, 'first_name' => 'Synthetic', 'last_name' => $id, 'birth_date' => '1941-01-01', 'sex' => 'FEMALE', 'address' => 'Synthetic address', 'status' => $status, 'verified_at' => '2026-01-01', 'id_generated_at' => '2026-01-01']));
    }

    private function csvRows(string $content): array
    {
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, $content);
        rewind($stream);
        $rows = [];
        while (($row = fgetcsv($stream, escape: '')) !== false) {
            $rows[] = $row;
        }
        fclose($stream);

        return $rows;
    }
}
