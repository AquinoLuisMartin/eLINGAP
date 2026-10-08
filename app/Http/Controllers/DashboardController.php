<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Enums\SeniorCitizenStatus;
use App\Models\Application;
use App\Models\SeniorCitizen;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        Gate::authorize('viewAny', SeniorCitizen::class);
        $active = SeniorCitizen::query()->where('status', SeniorCitizenStatus::Verified->value);
        $ageGroups = [
            '60–69' => (clone $active)->where('birth_date', '>', today()->subYears(70))->count(),
            '70–79' => (clone $active)->whereBetween('birth_date', [today()->subYears(80)->addDay(), today()->subYears(70)])->count(),
            '80–89' => (clone $active)->whereBetween('birth_date', [today()->subYears(90)->addDay(), today()->subYears(80)])->count(),
            '90+' => (clone $active)->where('birth_date', '<=', today()->subYears(90))->count(),
        ];

        return view('dashboard.index', [
            'activeCount' => (clone $active)->count(),
            'readyCount' => Application::query()->where('status', ApplicationStatus::Approved->value)->count(),
            'milestoneCount' => (clone $active)->where(function ($query) {
                foreach ([80, 90, 100] as $age) {
                    $query->orWhereBetween('birth_date', [today()->subYears($age), today()->subYears($age - 1)->subDay()]);
                }
            })->count(),
            'barangays' => (clone $active)->join('barangays', 'barangays.id', '=', 'senior_citizens.barangay_id')
                ->selectRaw('barangays.name, count(*) as total')->groupBy('barangays.id', 'barangays.name')
                ->orderByDesc('total')->limit(8)->get(),
            'ageGroups' => collect($ageGroups),
            'genders' => (clone $active)->selectRaw('sex, count(*) as total')->groupBy('sex')->pluck('total', 'sex'),
            'statuses' => SeniorCitizen::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }
}
