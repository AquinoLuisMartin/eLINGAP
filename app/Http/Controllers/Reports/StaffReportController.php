<?php

namespace App\Http\Controllers\Reports;

use App\Enums\ApplicationStatus;
use App\Enums\BenefitStatus;
use App\Enums\PayoutStatus;
use App\Enums\SeniorCitizenStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\Program;
use App\Services\Reports\StaffReport;
use App\Services\Reports\StaffReportPdf;
use App\Services\Reports\StaffReportSpreadsheet;
use App\Support\CsvCell;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class StaffReportController extends Controller
{
    public function show(Request $request, string $type, StaffReport $report): View
    {
        Gate::authorize('viewAny', StaffReport::class);
        abort_unless(isset(StaffReport::TYPES[$type]), 404);
        $filters = $this->filters($request, $type, $report);

        return view('reports.workspace', [
            'type' => $type,
            'title' => StaffReport::TYPES[$type],
            'headings' => $report->headings($type),
            'records' => $report->orderedQuery($type, $filters)->paginate(20)->withQueryString(),
            'report' => $report,
            'filters' => $filters,
            'filterLabels' => $report->filterLabels($filters),
            'summary' => $report->summary($type, $filters),
            'generatedAt' => now(),
            'population' => $report->populationQuery()->orderBy('name')->get(),
            'milestoneAges' => $type === 'milestones' ? $report->milestoneAges() : collect(),
            'barangays' => Barangay::query()->orderBy('name')->get(['id', 'name']),
            'programs' => $type !== 'archived' ? Program::query()->orderBy('name')->get(['id', 'name']) : collect(),
        ]);
    }

    public function export(Request $request, string $type, StaffReport $report, StaffReportPdf $pdf, StaffReportSpreadsheet $spreadsheet): Response
    {
        Gate::authorize('viewAny', StaffReport::class);
        abort_unless(isset(StaffReport::TYPES[$type]), 404);
        $filters = $this->filters($request, $type, $report);
        $format = $request->validate(['format' => ['sometimes', 'required', Rule::in(['csv', 'pdf', 'xlsx'])]])['format'] ?? 'csv';
        $generatedAt = now();
        $filterLabels = $report->filterLabels($filters);
        $summary = $report->summary($type, $filters);
        $query = $report->orderedQuery($type, $filters);
        $total = (clone $query)->count();
        if ($format === 'pdf' && $total > StaffReportPdf::MAX_RECORDS) {
            throw ValidationException::withMessages(['format' => 'PDF exports support up to '.StaffReportPdf::MAX_RECORDS.' records. Narrow the filters or export CSV for the complete report.']);
        }
        $description = match ($type) {
            'masterlist' => 'Senior citizens',
            'beneficiaries' => 'Program beneficiaries',
            default => StaffReport::TYPES[$type],
        };
        $filename = collect([$filterLabels['Barangay'] ?? 'All Barangays', $filterLabels['Program'] ?? null, $description])
            ->filter()
            ->map(fn (string $part) => Str::of(Str::ascii($part))
                ->replaceMatches('/[<>:"\/\\\\|?*%\x00-\x1F\x7F]/', ' ')
                ->squish()->limit(60, '')->trim(' .')->toString())
            ->filter()->push($generatedAt->format('Y-m-d'))->implode(' - ').'.'.$format;
        if ($format === 'pdf') {
            $content = $pdf->render([
                'title' => StaffReport::TYPES[$type], 'type' => $type, 'report' => $report,
                'headings' => $report->headings($type), 'records' => $query->get(),
                'generatedAt' => $generatedAt, 'filterLabels' => $filterLabels, 'total' => $total, 'summary' => $summary,
            ]);
        } else {
            $population = $report->populationQuery()->orderBy('name')->get();
        }
        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'report.exported', 'new_values' => ['report' => $type, 'format' => $format, 'filters' => $filters, 'generated_at' => $generatedAt->toIso8601String()], 'ip_address' => $request->ip()]);

        if ($format === 'pdf') {
            return response($content, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.$filename.'"', 'Cache-Control' => 'private, no-store']);
        }

        if ($format === 'xlsx') {
            return response()->streamDownload(function () use ($spreadsheet, $report, $type, $query, $generatedAt, $population, $filterLabels, $summary): void {
                $spreadsheet->write([
                    'title' => StaffReport::TYPES[$type], 'type' => $type, 'report' => $report,
                    'headings' => $report->headings($type), 'records' => $query->lazy(200),
                    'generatedAt' => $generatedAt, 'population' => $population, 'filterLabels' => $filterLabels, 'summary' => $summary,
                ]);
            }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Cache-Control' => 'private, no-store']);
        }

        return response()->streamDownload(function () use ($report, $type, $query, $generatedAt, $population, $filterLabels, $total, $summary): void {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, $report->headings($type), escape: '');
            foreach ($query->lazy(200) as $record) {
                fputcsv($stream, array_map(CsvCell::escape(...), $report->row($type, $record)), escape: '');
            }
            fputcsv($stream, [], escape: '');
            foreach (['Report' => StaffReport::TYPES[$type], 'Generated at' => $generatedAt->format('Y-m-d H:i:s e'), 'Matching records' => $total, ...$filterLabels, ...$summary] as $label => $value) {
                fputcsv($stream, array_map(CsvCell::escape(...), [$label, $value]), escape: '');
            }
            fputcsv($stream, ['Population scope', 'Verified senior citizens across all barangays; excludes deceased, archived and transferred records.'], escape: '');
            fputcsv($stream, ['Barangay', 'Verified senior population'], escape: '');
            foreach ($population as $barangay) {
                fputcsv($stream, [CsvCell::escape($barangay->name), (int) $barangay->population], escape: '');
            }
            fputcsv($stream, ['Total verified senior population', $population->sum('population')], escape: '');
            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }

    private function filters(Request $request, string $type, StaffReport $report): array
    {
        $rules = ['barangay_id' => ['nullable', 'integer', 'exists:barangays,id'], 'search' => ['nullable', 'string', 'max:100']];
        if (in_array($type, ['masterlist', 'applications'], true)) {
            $rules['status'] = ['nullable', Rule::enum(match ($type) {
                'masterlist' => SeniorCitizenStatus::class,
                'applications' => ApplicationStatus::class,
            })];
        }
        if ($type === 'payouts') {
            $rules['status'] = ['nullable', Rule::in(array_unique([...array_column(PayoutStatus::cases(), 'value'), ...array_column(BenefitStatus::cases(), 'value')]))];
        }
        if ($type === 'archived') {
            $rules['status'] = ['nullable', Rule::in(['DECEASED', 'ARCHIVED', 'TRANSFERRED'])];
        }
        if ($type !== 'archived') {
            $rules['program_id'] = ['nullable', 'integer', 'exists:programs,id'];
        }
        if (in_array($type, ['masterlist', 'beneficiaries', 'population', 'milestones', 'archived'], true)) {
            $rules['age_min'] = ['nullable', 'integer', 'min:60', 'max:150'];
            $rules['age_max'] = ['nullable', 'integer', 'min:60', 'max:150', Rule::when($request->filled('age_min'), 'gte:age_min')];
        }
        if ($type === 'milestones') {
            $rules['milestone'] = ['nullable', Rule::in($report->milestoneAges()->all())];
        }
        if (in_array($type, ['applications', 'payouts', 'proxy'], true)) {
            $rules['from'] = ['nullable', 'date'];
            $rules['to'] = ['nullable', 'date', 'after_or_equal:from'];
        }

        $filters = $request->validate($rules);
        if ($type === 'milestones') {
            $filters['milestone'] ??= $report->milestoneAges()->first();
        }

        return $filters;
    }
}
