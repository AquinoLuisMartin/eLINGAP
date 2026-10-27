<?php

namespace App\Livewire\Benefits;

use App\Models\BenefitEntitlement;
use App\Models\BenefitPayment;
use App\Models\BenefitSchedule;
use App\Models\Program;
use App\Services\Benefits\BenefitAudit;
use App\Services\Benefits\BenefitPayments;
use App\Services\Benefits\BenefitScheduling;
use App\Support\CsvCell;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EntitlementReport extends Component
{
    use WithPagination;

    public string $status = '';

    public string $search = '';

    public string $programId = '';

    public string $period = '';

    public string $targetSchedule = '';

    public string $reason = '';

    public string $notice = '';

    public function boot(): void
    {
        Gate::authorize('viewAny', BenefitSchedule::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['status', 'search', 'programId', 'period'], true)) {
            $this->resetPage('entitlementsPage');
        }
    }

    private function query(): Builder
    {
        return BenefitEntitlement::query()->select(['id', 'program_id', 'senior_citizen_id', 'benefit_schedule_id', 'period_key', 'amount', 'status'])
            ->with([
                'program:id,name,government_level',
                'seniorCitizen:id,barangay_id,osca_id_number,first_name,middle_name,last_name,name_suffix,status',
                'seniorCitizen.barangay:id,name', 'schedule:id,status',
                'payments:id,benefit_entitlement_id,reference_number,paid_at,reversed_at,reversal_reason',
            ])
            ->when(in_array($this->status, ['PENDING', 'UNPAID', 'UNCLAIMED', 'CLAIMED']), fn ($query) => $query->where('status', $this->status))
            ->when($this->programId, fn ($query) => $query->where('program_id', (int) $this->programId))
            ->when(trim($this->period) !== '', fn ($query) => $query->where('period_key', trim($this->period)))
            ->when(trim($this->search) !== '', fn ($query) => $query->whereHas('seniorCitizen', fn ($query) => $query->searchable(trim($this->search))))
            ->orderBy('id');
    }

    public function reschedule(int $id, BenefitScheduling $service): void
    {
        $this->validate(['targetSchedule' => ['required', 'integer', 'exists:benefit_schedules,id']]);
        $service->reschedule(BenefitEntitlement::findOrFail($id), BenefitSchedule::findOrFail($this->targetSchedule));
        $this->notice = 'Original entitlement moved for staff review. Its period and recorded amount are unchanged.';
    }

    public function reverse(int $paymentId, BenefitPayments $service): void
    {
        $service->reverse(BenefitPayment::findOrFail($paymentId), $this->reason);
        $this->notice = 'Payment reversed. Original reference and history retained.';
        $this->reset('reason');
    }

    public function export(): StreamedResponse
    {
        Gate::authorize('viewAny', BenefitSchedule::class);
        abort_unless(RateLimiter::attempt('benefit-export:'.auth()->id(), 3, fn () => true, 60), 429, 'Please wait before exporting another report.');
        $query = $this->query();
        $first = (clone $query)->withoutEagerLoads()->first(['id']);
        if ($first) {
            BenefitAudit::record('benefit.report_exported', $first, ['status' => $this->status, 'program_id' => $this->programId, 'period' => $this->period]);
        }

        return response()->streamDownload(function () use ($query): void {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['OSCA ID No.', 'Program', 'Original period', 'Amount (PHP)', 'Status', 'Senior status', 'Payment references'], escape: '');
            $query->chunkById(200, function ($entries) use ($stream): void {
                foreach ($entries as $entry) {
                    $row = [$entry->seniorCitizen->osca_id_number ?? '', $entry->program->name, $entry->period_key, $entry->amount ?? '', $entry->status->value, $entry->seniorCitizen->status->value, $entry->payments->map(fn ($payment) => $payment->reference_number.($payment->reversed_at ? ' (reversed)' : ''))->implode('; ')];
                    fputcsv($stream, array_map(CsvCell::escape(...), $row), escape: '');
                }
            });
            fclose($stream);
        }, 'benefit-entitlements.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }

    public function render(): View
    {
        return view('livewire.benefits.entitlement-report', [
            'entries' => $this->query()->paginate(15, pageName: 'entitlementsPage'),
            'counts' => BenefitEntitlement::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'programs' => Program::query()->whereNotNull('benefit_code')->orderBy('name')->get(['id', 'name']),
            'drafts' => BenefitSchedule::query()->where('status', 'DRAFT')->with('program:id,name')->latest('id')->get(['id', 'program_id', 'reference_number', 'batch_name']),
        ]);
    }
}
