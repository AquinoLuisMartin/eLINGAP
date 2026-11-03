<?php

namespace App\Services\SeniorCitizens;

use App\Models\Barangay;
use App\Models\Program;
use App\Models\SeniorCitizen;

class RegistrySearch
{
    public static function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'search_by' => ['nullable', 'in:all,name,osca_id'],
            'barangay_id' => ['nullable', 'integer', 'exists:barangays,id'],
            'status' => ['nullable', 'in:PENDING,VERIFIED,ARCHIVED,DECEASED,TRANSFERRED'],
            'age' => ['nullable', 'in:60-69,70-79,80+'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'benefit' => ['nullable', 'in:PENSIONER,NATIONAL_PENSION,LOCAL_PENSION,NONE'],
            'sort' => ['nullable', 'in:id,last_name,first_name,middle_name,benefits'],
            'direction' => ['nullable', 'in:asc,desc'],
        ];
    }

    public function data(array $filters, int $page = 1): array
    {
        $query = SeniorCitizen::query()->with(['barangay:id,name', 'activeAssignments:id,senior_citizen_id,program_id', 'activeAssignments.program:id,name', 'milestoneClaims:id,senior_citizen_id,milestone_age'])
            ->when(filled(trim($filters['search'] ?? '')), fn ($query) => $query->searchable($filters['search'], $filters['search_by'] ?? 'all'))
            ->when($filters['barangay_id'] ?? null, fn ($query, $id) => $query->where('barangay_id', $id))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['age'] ?? null, function ($query, $age) {
                match ($age) {
                    '60-69' => $query->where('birth_date', '>', today()->subYears(70)),
                    '70-79' => $query->whereBetween('birth_date', [today()->subYears(80)->addDay(), today()->subYears(70)]),
                    '80+' => $query->where('birth_date', '<=', today()->subYears(80)),
                };
            })
            ->when($filters['program_id'] ?? null, fn ($query, $programId) => $query->whereHas('activeAssignments', fn ($assignments) => $assignments->where('program_id', $programId)))
            ->when($filters['benefit'] ?? null, function ($query, $benefit) {
                match ($benefit) {
                    'PENSIONER' => $query->where(fn ($pensions) => $pensions->where('has_national_pension', true)->orWhere('has_local_pension', true)),
                    'NATIONAL_PENSION' => $query->where('has_national_pension', true),
                    'LOCAL_PENSION' => $query->where('has_local_pension', true),
                    'NONE' => $query->whereDoesntHave('activeAssignments')->where('has_national_pension', false)->where('has_local_pension', false),
                };
            });
        $sort = filled($filters['sort'] ?? null) ? $filters['sort'] : 'id';
        $direction = filled($filters['direction'] ?? null) ? $filters['direction'] : ($sort === 'id' ? 'desc' : 'asc');
        if ($sort === 'benefits') {
            $query->orderBy(Program::query()->select('name')->whereHas('beneficiaries', fn ($assignments) => $assignments->whereColumn('senior_citizen_id', 'senior_citizens.id')->where('status', 'ACTIVE'))->orderBy('name')->limit(1), $direction)
                ->orderBy('has_national_pension', $direction)->orderBy('has_local_pension', $direction);
        } else {
            $query->orderBy($sort, $direction);
        }

        $counts = (clone $query)->reorder()->toBase()->selectRaw(
            'count(*) as total, count(case when birth_date > ? then 1 end) as age_60_69, count(case when birth_date between ? and ? then 1 end) as age_70_79, count(case when birth_date <= ? then 1 end) as age_80_plus',
            [today()->subYears(70), today()->subYears(80)->addDay(), today()->subYears(70), today()->subYears(80)]
        )->first();

        return [
            'seniorCitizens' => (clone $query)->select(['id', 'barangay_id', 'osca_id_number', 'first_name', 'middle_name', 'last_name', 'name_suffix', 'birth_date', 'status', 'verified_at', 'id_generated_at', 'has_national_pension', 'has_local_pension'])->orderBy('id')->paginate(15, ['*'], 'page', $page)->withPath(route('senior-citizens.index'))->appends($filters),
            'counts' => [
                'Total registry' => (int) $counts->total,
                'Age 60–69' => (int) $counts->age_60_69,
                'Age 70–79' => (int) $counts->age_70_79,
                'Age 80+' => (int) $counts->age_80_plus,
            ],
            'barangays' => Barangay::query()->orderBy('name')->get(['id', 'name']),
            'programs' => Program::query()->orderBy('name')->get(['id', 'name']),
        ];
    }
}
