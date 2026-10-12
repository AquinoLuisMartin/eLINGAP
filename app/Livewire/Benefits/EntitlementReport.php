<?php

namespace App\Livewire\Benefits;

use App\Models\BenefitEntitlement;
use App\Models\BenefitPayment;
use App\Models\BenefitSchedule;
use App\Models\Program;
use App\Services\Benefits\BenefitAudit;
use App\Services\Benefits\BenefitPayments;
use App\Services\Benefits\BenefitScheduling;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

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
            $this->resetPage();
        }
    }

    private function query()
    {
        return BenefitEntitlement::query()->with(['program', 'seniorCitizen.barangay', 'schedule', 'payments'])
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

    public function export()
    {
        Gate::authorize('viewAny', BenefitSchedule::class);
        $query = $this->query();
        $first = (clone $query)->first();
        if ($first) {
            BenefitAudit::record('benefit.report_exported', $first, ['status' => $this->status, 'program_id' => $this->programId, 'period' => $this->period]);
        }

        return response()->streamDownload(function () use ($query): void {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['Registration ID', 'Program', 'Original period', 'Amount (PHP)', 'Status', 'Senior status', 'Payment references'], escape: '');
            $query->chunkById(200, function ($entries) use ($stream): void {
                foreach ($entries as $entry) {
                    $row = [$entry->seniorCitizen->registration_number, $entry->program->name, $entry->period_key, $entry->amount ?? '', $entry->status->value, $entry->seniorCitizen->status->value, $entry->payments->map(fn ($payment) => $payment->reference_number.($payment->reversed_at ? ' (reversed)' : ''))->implode('; ')];
                    fputcsv($stream, array_map(fn ($cell) => preg_match('/^[\s]*[=+@-]/u', (string) $cell) ? "'".$cell : $cell, $row), escape: '');
                }
            });
            fclose($stream);
        }, 'benefit-entitlements.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }

    public function render()
    {
        return view('livewire.benefits.entitlement-report', [
            'entries' => $this->query()->paginate(15),
            'counts' => BenefitEntitlement::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'programs' => Program::query()->whereNotNull('benefit_code')->orderBy('name')->get(['id', 'name']),
            'drafts' => BenefitSchedule::query()->where('status', 'DRAFT')->with('program:id,name')->latest('id')->get(),
        ]);
    }
}
