<?php

namespace App\Http\Controllers\Programs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Programs\StoreBeneficiaryRequest;
use App\Models\Beneficiary;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BeneficiaryController extends Controller
{
    public function index(Program $program): View
    {
        Gate::authorize('view', $program);

        return view('programs.beneficiaries.index', [
            'program' => $program,
            'beneficiaries' => $program->beneficiaries()->with('seniorCitizen')->latest('id')->paginate(15),
        ]);
    }

    public function store(StoreBeneficiaryRequest $request, Program $program): RedirectResponse
    {
        Gate::authorize('update', $program);

        Beneficiary::create([...$request->validated(), 'program_id' => $program->id]);

        return back()->with('status', 'Beneficiary enrolled.');
    }
}
