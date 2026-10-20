<?php

namespace App\Services\Reports;

use App\Models\Application;
use App\Models\Payout;
use App\Models\Program;
use App\Models\SeniorCitizen;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StaffReport
{
    public const TYPES = [
        'masterlist' => 'Senior citizen masterlist',
        'applications' => 'Benefit application status log',
        'payouts' => 'Benefits and payout summary',
        'milestones' => 'Milestone age qualifiers',
        'proxy' => 'Proxy payout claims',
        'archived' => 'Deceased and archived seniors log',
    ];

    public function milestoneAges(): Collection
    {
        return Program::query()->where('status', 'ACTIVE')->get(['benefit_rules'])
            ->flatMap(fn ($program) => array_keys($program->benefit_rules['milestones'] ?? []))
            ->map(fn ($age) => (int) $age)->filter(fn ($age) => $age >= 60)->unique()->sort()->values();
    }

    public function query(string $type, array $filters): Builder
    {
        return match ($type) {
            'masterlist', 'milestones', 'archived' => $this->seniors($type, $filters),
            'applications' => Application::query()->select(['id', 'senior_citizen_id', 'program_id', 'application_number', 'applied_on', 'status'])
                ->with(['seniorCitizen:id,barangay_id,first_name,middle_name,last_name,name_suffix', 'seniorCitizen.barangay:id,name', 'program:id,name'])
                ->when($filters['barangay_id'] ?? null, fn ($query, $id) => $query->whereHas('seniorCitizen', fn ($senior) => $senior->where('barangay_id', $id)))
                ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
                ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('applied_on', '>=', $date))
                ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('applied_on', '<=', $date)),
            'payouts', 'proxy' => Payout::query()->select(['id', 'senior_citizen_id', 'payout_schedule_id', 'amount', 'claimant_name', 'status'])
                ->with(['seniorCitizen:id,barangay_id,osca_id_number,first_name,middle_name,last_name,name_suffix', 'seniorCitizen.barangay:id,name', 'schedule:id,program_id', 'schedule.program:id,name'])
                ->when($type === 'proxy', fn ($query) => $query->where('claimant_type', 'PROXY')->where('status', 'RELEASED'))
                ->when($filters['barangay_id'] ?? null, fn ($query, $id) => $query->whereHas('seniorCitizen', fn ($senior) => $senior->where('barangay_id', $id)))
                ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
                ->when($filters['program_id'] ?? null, fn ($query, $id) => $query->whereHas('schedule', fn ($schedule) => $schedule->where('program_id', $id)))
                ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereHas('schedule', fn ($schedule) => $schedule->whereDate('scheduled_on', '>=', $date)))
                ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereHas('schedule', fn ($schedule) => $schedule->whereDate('scheduled_on', '<=', $date))),
        };
    }

    private function seniors(string $type, array $filters): Builder
    {
        return SeniorCitizen::query()->select(['id', 'barangay_id', 'osca_id_number', 'first_name', 'middle_name', 'last_name', 'name_suffix', 'birth_date', 'status'])
            ->with('barangay:id,name')
            ->when($type === 'archived', fn ($query) => $query->whereIn('status', ['ARCHIVED', 'DECEASED', 'TRANSFERRED']))
            ->when($type === 'milestones', function ($query) use ($filters) {
                $age = $filters['milestone'] ?? $this->milestoneAges()->first();
                if ($age === null) {
                    $query->whereRaw('1 = 0');

                    return;
                }
                $age = (int) $age;
                $query->where('status', 'VERIFIED')->where('birth_date', '<=', today()->subYears($age));
            })
            ->when($filters['barangay_id'] ?? null, fn ($query, $id) => $query->where('barangay_id', $id))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status));
    }

    public function headings(string $type): array
    {
        return match ($type) {
            'masterlist', 'milestones', 'archived' => ['OSCA ID', 'Name', 'Birth date', 'Age', 'Barangay', 'Status'],
            'applications' => ['Reference', 'Senior', 'Barangay', 'Program', 'Applied on', 'Status'],
            'payouts', 'proxy' => ['OSCA ID', 'Senior', 'Barangay', 'Program', 'Amount', 'Claimant', 'Status'],
        };
    }

    public function row(string $type, mixed $record): array
    {
        return match ($type) {
            'masterlist', 'milestones', 'archived' => [$record->osca_id_number ?: '—', $record->full_name, $record->birth_date->format('Y-m-d'), $record->birth_date->age, $record->barangay->name, $record->status->value],
            'applications' => [$record->application_number, $record->seniorCitizen->full_name, $record->seniorCitizen->barangay->name, $record->program->name, $record->applied_on->format('Y-m-d'), $record->status->value],
            'payouts', 'proxy' => [$record->seniorCitizen->osca_id_number ?: '—', $record->seniorCitizen->full_name, $record->seniorCitizen->barangay->name, $record->schedule->program->name, $record->amount, $record->claimant_name ?: '—', $record->status->value],
        };
    }
}
