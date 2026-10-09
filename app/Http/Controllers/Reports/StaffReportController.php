<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\Program;
use App\Services\Reports\StaffReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffReportController extends Controller
{
    public function show(Request $request, string $type, StaffReport $report): View
    {
        Gate::authorize('viewAny', StaffReport::class);
        abort_unless(isset(StaffReport::TYPES[$type]), 404);
        $filters = $this->filters($request, $type);

        return view('reports.workspace', [
            'type' => $type,
            'title' => StaffReport::TYPES[$type],
            'headings' => $report->headings($type),
            'records' => $report->query($type, $filters)->latest('id')->paginate(20)->withQueryString(),
            'report' => $report,
            'filters' => $filters,
            'barangays' => Barangay::query()->orderBy('name')->get(['id', 'name']),
            'programs' => $type === 'payouts' ? Program::query()->orderBy('name')->get(['id', 'name']) : collect(),
        ]);
    }

    public function export(Request $request, string $type, StaffReport $report): StreamedResponse
    {
        Gate::authorize('viewAny', StaffReport::class);
        abort_unless(isset(StaffReport::TYPES[$type]), 404);
        $filters = $this->filters($request, $type);
        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'report.exported', 'new_values' => ['report' => $type, 'filters' => $filters], 'ip_address' => $request->ip()]);

        return response()->streamDownload(function () use ($report, $type, $filters): void {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, $report->headings($type), escape: '');
            foreach ($report->query($type, $filters)->lazyById(200) as $record) {
                fputcsv($stream, array_map(fn ($value) => is_string($value) && preg_match('/^(?:[\x00-\x20]*[=+\-@]|[\t\r\n])/', $value) ? "'".$value : $value, $report->row($type, $record)), escape: '');
            }
            fclose($stream);
        }, $type.'-'.today()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filters(Request $request, string $type): array
    {
        $rules = ['barangay_id' => ['nullable', 'integer', 'exists:barangays,id']];
        if (in_array($type, ['masterlist', 'applications', 'payouts'], true)) {
            $rules['status'] = ['nullable', 'in:'.match ($type) {
                'masterlist' => 'PENDING,VERIFIED,ARCHIVED,DECEASED',
                'applications' => 'PENDING,APPROVED,REJECTED,CANCELLED',
                'payouts' => 'PENDING,RELEASED,FAILED,VOIDED',
            }];
        }
        if ($type === 'payouts') {
            $rules['program_id'] = ['nullable', 'integer', 'exists:programs,id'];
        }
        if ($type === 'milestones') {
            $rules['milestone'] = ['nullable', 'in:80,90,100'];
        }
        if (in_array($type, ['applications', 'payouts', 'proxy'], true)) {
            $rules['from'] = ['nullable', 'date'];
            $rules['to'] = ['nullable', 'date', 'after_or_equal:from'];
        }

        return $request->validate($rules);
    }
}
