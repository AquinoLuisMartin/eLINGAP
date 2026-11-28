<?php

namespace App\Http\Controllers\SeniorCitizens;

use App\Http\Controllers\Controller;
use App\Models\IdPrintAttempt;
use App\Models\SeniorCitizen;
use App\Services\SeniorCitizens\IdCardLayout;
use App\Services\SeniorCitizens\IdPrinting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

    public function print(Request $request, SeniorCitizen $seniorCitizen, IdPrinting $printing): Response
    {
        Gate::authorize('manageId', $seniorCitizen);
        $data = $request->validate(['paper' => ['required', Rule::in(array_keys(IdCardLayout::PAPERS))]]);
        [, $pdf, $attempts] = $printing->generate([$seniorCitizen->id], $data['paper']);

        return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="'.$this->filename($seniorCitizen->last_name).'"', 'Cache-Control' => 'private, no-store', 'X-ID-Print-Attempts' => json_encode(array_column($attempts, 'id'))]);
    }

    public function officials(): View
    {
        Gate::authorize('configureId', SeniorCitizen::class);

        return view('senior-citizens.ids.officials');
    }

    public function batchPrint(Request $request, IdPrinting $printing): Response
    {
        Gate::authorize('configureId', SeniorCitizen::class);
        $data = $request->validate([
            'senior_ids' => ['required', 'array', 'min:1', 'max:4'],
            'senior_ids.*' => ['required', 'integer', 'distinct', 'exists:senior_citizens,id'],
            'paper' => ['required', Rule::in(array_keys(IdCardLayout::PAPERS))],
            'reviewed' => ['required', 'accepted'],
            'layout' => ['sometimes', 'required', 'in:single,batch'],
        ], [
            'senior_ids.required' => 'Select at least one ID to print.',
            'senior_ids.min' => 'Select at least one ID to print.',
            'senior_ids.max' => 'Select no more than four IDs for a batch.',
            'senior_ids.*.required' => 'A selected ID is missing. Refresh the page and select the records again.',
            'senior_ids.*.integer' => 'A selected ID is invalid. Refresh the page and select the records again.',
            'senior_ids.*.distinct' => 'Select each ID only once.',
            'senior_ids.*.exists' => 'A selected record is no longer available. Refresh the page and select the records again.',
        ]);
        if (($data['layout'] ?? 'batch') === 'single' && count($data['senior_ids']) !== 1) {
            throw ValidationException::withMessages(['senior_ids' => 'Select exactly one ID for the Single ID layout.']);
        }
        [$seniors, $pdf, $attempts] = $printing->generate($data['senior_ids'], $data['paper'], $data['layout'] ?? 'batch', issuePending: true);

        $filename = $seniors->count() === 1 ? $this->filename($seniors->first()->last_name) : 'senior-ids.pdf';

        return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="'.$filename.'"', 'Cache-Control' => 'private, no-store', 'X-ID-Print-Attempts' => json_encode(array_column($attempts, 'id'))]);
    }

    public function pendingPrints(): JsonResponse
    {
        Gate::authorize('configureId', SeniorCitizen::class);
        $attempts = IdPrintAttempt::query()->where('status', 'GENERATED')
            ->where(fn ($query) => $query->where('generated_by', auth()->id())->orWhereNotNull('legacy_audit_id'))
            ->with('seniorCitizen:id,first_name,middle_name,last_name,name_suffix,osca_id_number')->orderBy('id')->get();

        return response()->json(['attempts' => $attempts->map(fn ($attempt) => [
            'id' => $attempt->id, 'name' => $attempt->seniorCitizen->full_name, 'osca_id' => $attempt->seniorCitizen->osca_id_number,
        ])])->header('Cache-Control', 'private, no-store');
    }

    public function completePrinting(Request $request, IdPrinting $printing): JsonResponse
    {
        Gate::authorize('configureId', SeniorCitizen::class);
        $data = $request->validate([
            'results' => ['required', 'array', 'min:1', 'max:4'],
            'results.*' => ['required', 'array:attempt_id,result'],
            'results.*.attempt_id' => ['required', 'integer', 'distinct'],
            'results.*.result' => ['required', 'in:PRINTED,NOT_PRINTED'],
        ]);
        $printing->complete($data['results']);

        return response()->json(['message' => 'Printing results saved. Successfully printed IDs are retained in the Senior Masterlist and registration history.']);
    }

    private function filename(?string $lastName): string
    {
        $name = preg_replace('/[^A-Z0-9_-]+/', '_', Str::upper(Str::ascii(trim((string) $lastName))));

        return (trim((string) $name, '_-') ?: 'SENIOR-ID').'.pdf';
    }
}
