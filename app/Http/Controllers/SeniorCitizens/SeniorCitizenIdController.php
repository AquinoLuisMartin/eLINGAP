<?php

namespace App\Http\Controllers\SeniorCitizens;

use App\Http\Controllers\Controller;
use App\Models\SeniorCitizen;
use App\Services\Benefits\BenefitAudit;
use App\Services\SeniorCitizens\IdCardLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SeniorCitizenIdController extends Controller
{
    public function show(SeniorCitizen $seniorCitizen): View
    {
        Gate::authorize('manageId', $seniorCitizen);

        return view('senior-citizens.ids.manage', ['senior' => $seniorCitizen]);
    }

    public function preview(SeniorCitizen $seniorCitizen, IdCardLayout $layout): View
    {
        Gate::authorize('manageId', $seniorCitizen);

        return view('senior-citizens.ids.preview', ['card' => $layout->data($seniorCitizen, true)]);
    }

    public function print(Request $request, SeniorCitizen $seniorCitizen, IdCardLayout $layout): Response
    {
        Gate::authorize('manageId', $seniorCitizen);
        $data = $request->validate(['paper' => ['required', Rule::in(array_keys(IdCardLayout::PAPERS))], 'purpose' => ['required', Rule::in(['FIRST_PRINT', 'REPRINT', 'REPLACEMENT'])]]);
        $pdf = $layout->pdf($layout->data($seniorCitizen), $data['paper']);
        BenefitAudit::record('senior_citizen.id_print_generated', $seniorCitizen, [...$data, 'generated_at' => now()->toIso8601String()]);

        return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="senior-id.pdf"', 'Cache-Control' => 'private, no-store']);
    }

    public function officials(): View
    {
        Gate::authorize('configureId', SeniorCitizen::class);

        return view('senior-citizens.ids.officials');
    }
}
