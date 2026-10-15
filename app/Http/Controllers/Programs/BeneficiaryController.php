<?php

namespace App\Http\Controllers\Programs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Programs\StoreBeneficiaryRequest;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Services\Benefits\BenefitAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BeneficiaryController extends Controller
{
    public function index(Request $request, Program $program): View|RedirectResponse
    {
        Gate::authorize('view', $program);
        if ($program->benefit_code) {
            return redirect()->route('benefits.workspace');
        }

        $search = $request->validate(['senior_search' => ['nullable', 'string', 'max:100']])['senior_search'] ?? null;

        return view('programs.beneficiaries.index', [
            'program' => $program,
            'beneficiaries' => $program->beneficiaries()->with('seniorCitizen.barangay')->latest('id')->paginate(15),
            'seniorCitizens' => $search ? SeniorCitizen::query()->where('status', 'VERIFIED')->whereNotNull('verified_at')->whereNotNull('id_generated_at')->whereNotNull('osca_id_number')->searchable($search)->orderBy('last_name')->limit(25)->get() : collect(),
        ]);
    }

    public function store(StoreBeneficiaryRequest $request, Program $program, BenefitAssignment $assignments): RedirectResponse
    {
        $assignments->assign(SeniorCitizen::findOrFail($request->integer('senior_citizen_id')), $program, $request->validated());

        return back()->with('status', 'Beneficiary enrolled.');
    }
}
