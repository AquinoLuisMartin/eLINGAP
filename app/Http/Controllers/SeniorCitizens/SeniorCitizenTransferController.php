<?php

namespace App\Http\Controllers\SeniorCitizens;

use App\Http\Controllers\Controller;
use App\Http\Requests\SeniorCitizens\StoreTransferRequest;
use App\Models\SeniorCitizen;
use App\Services\Benefits\BenefitAudit;
use App\Services\SeniorCitizens\IdAssets;
use App\Services\SeniorCitizens\IdOfficials;
use App\Services\SeniorCitizens\SeniorTransfer;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class SeniorCitizenTransferController extends Controller
{
    public function store(StoreTransferRequest $request, SeniorCitizen $seniorCitizen, SeniorTransfer $transfers): RedirectResponse
    {
        $transfers->record($seniorCitizen, $request->validated());

        return redirect()->route('senior-citizens.show', [$seniorCitizen, 'section' => 'transfers'])->with('status', 'Transfer recorded.');
    }

    public function certificate(SeniorCitizen $seniorCitizen, int $transfer, IdOfficials $officials, IdAssets $assets): Response
    {
        Gate::authorize('manageId', $seniorCitizen);
        $transfer = $seniorCitizen->transfers()->findOrFail($transfer);
        abort_unless($transfer->type === 'EXTERNAL', 422, 'Cancellation certificates apply only to transfers outside Santa Maria.');
        $settings = $officials->values();
        if (blank($settings['osca_head_name'] ?? null)) {
            throw ValidationException::withMessages(['officials' => 'Configure the OSCA Head before generating the certificate.']);
        }
        $seal = $assets->municipalSealDataUri($settings['municipal_seal'] ?? null);
        $signature = $assets->pathDataUri($settings['osca_head_signature'] ?? null);
        if (! function_exists('imagecreatefrompng') && collect([$seal, $signature])->contains(fn ($image) => str_starts_with($image ?? '', 'data:image/png'))) {
            throw ValidationException::withMessages(['officials' => 'Prepare official PNG images through the image editor, or enable PHP GD before generating the certificate.']);
        }
        $options = new Options;
        $options->setIsRemoteEnabled(false);
        $options->setIsPhpEnabled(false);
        $options->setChroot(storage_path('app'));
        $options->setTempDir(storage_path('framework/cache'));
        $options->setFontCache(storage_path('framework/cache'));
        $pdf = new Dompdf($options);
        $pdf->setPaper('a4');
        $pdf->loadHtml(view('senior-citizens.transfers.certificate', [
            'transfer' => $transfer, 'officials' => $settings,
            'seal' => $seal, 'signature' => $signature,
        ])->render());
        $pdf->render();
        BenefitAudit::record('senior_citizen.cancellation_certificate_generated', $seniorCitizen, ['transfer_id' => $transfer->id]);

        return response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="certificate-of-cancellation.pdf"', 'Cache-Control' => 'private, no-store']);
    }
}
