<?php

namespace App\Http\Controllers\Programs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Programs\StoreBeneficiaryRequest;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\Program;
use App\Models\SeniorCitizen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BeneficiaryController extends Controller
{
    public function index(Request $request, Program $program): View
    {
        Gate::authorize('view', $program);

        $search = $request->validate(['senior_search' => ['nullable', 'string', 'max:100']])['senior_search'] ?? null;

        return view('programs.beneficiaries.index', [
            'program' => $program,
            'beneficiaries' => $program->beneficiaries()->with('seniorCitizen.barangay')->latest('id')->paginate(15),
            'seniorCitizens' => $search ? SeniorCitizen::query()->where('status', 'VERIFIED')->searchable($search)->orderBy('last_name')->limit(25)->get() : collect(),
        ]);
    }

    public function store(StoreBeneficiaryRequest $request, Program $program): RedirectResponse
    {
        Gate::authorize('update', $program);

        $beneficiary = Beneficiary::create([...$request->validated(), 'program_id' => $program->id]);
        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'beneficiary.enrolled', 'auditable_type' => Beneficiary::class, 'auditable_id' => $beneficiary->id, 'new_values' => ['program_id' => $program->id, 'senior_citizen_id' => $beneficiary->senior_citizen_id], 'ip_address' => $request->ip()]);

        return back()->with('status', 'Beneficiary enrolled.');
    }
}
