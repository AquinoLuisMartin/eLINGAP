<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Enums\SeniorCitizenStatus;
use App\Models\Application;
use App\Models\BenefitEntitlement;
use App\Models\Program;
use App\Models\SeniorCitizen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        if ($request->user()->isAdmin()) {
            return redirect()->route('administration.dashboard');
        }

        Gate::authorize('viewAny', SeniorCitizen::class);
        $active = SeniorCitizen::query()->where('status', SeniorCitizenStatus::Verified->value);
        $milestoneAges = Program::query()->where('status', 'ACTIVE')->get(['benefit_rules'])
            ->flatMap(fn ($program) => array_keys($program->benefit_rules['milestones'] ?? []))
            ->map(fn ($age) => (int) $age)->filter(fn ($age) => $age >= 60)->unique()->sort()->values();
        $totals = (clone $active)->toBase()->selectRaw(
            'count(*) as total, count(distinct barangay_id) as barangays, count(case when birth_date > ? then 1 end) as age_60_69, count(case when birth_date between ? and ? then 1 end) as age_70_79, count(case when birth_date between ? and ? then 1 end) as age_80_89, count(case when birth_date <= ? then 1 end) as age_90_plus',
            [today()->subYears(70), today()->subYears(80)->addDay(), today()->subYears(70), today()->subYears(90)->addDay(), today()->subYears(80), today()->subYears(90)]
        )->first();
        $ageGroups = [
            '60–69' => (int) $totals->age_60_69,
            '70–79' => (int) $totals->age_70_79,
            '80–89' => (int) $totals->age_80_89,
            '90+' => (int) $totals->age_90_plus,
        ];

        return view('dashboard.index', [
            'activeCount' => (int) $totals->total,
            'readyCount' => Application::query()->where('status', ApplicationStatus::Approved->value)->count(),
            'benefitCounts' => BenefitEntitlement::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'milestoneAges' => $milestoneAges,
            'milestoneCount' => $milestoneAges->isEmpty() ? 0 : (clone $active)->where(function ($query) use ($milestoneAges) {
                foreach ($milestoneAges as $age) {
                    $query->orWhereBetween('birth_date', [today()->subYears($age), today()->subYears($age - 1)->subDay()]);
                }
            })->count(),
            'barangayCount' => (int) $totals->barangays,
            'barangays' => (clone $active)->join('barangays', 'barangays.id', '=', 'senior_citizens.barangay_id')
                ->selectRaw('barangays.name, count(*) as total')->groupBy('barangays.id', 'barangays.name')
                ->orderByDesc('total')->limit(8)->get(),
            'ageGroups' => collect($ageGroups),
            'genders' => (clone $active)->selectRaw('sex, count(*) as total')->groupBy('sex')->pluck('total', 'sex'),
            'statuses' => SeniorCitizen::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }
}
