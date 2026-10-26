<?php

namespace App\Services\SeniorCitizens;

use App\Enums\SeniorCitizenStatus;
use App\Models\SeniorCitizen;
use App\Models\SeniorCitizenIdCredential;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Validation\ValidationException;

class IdCardLayout
{
    public const PAPERS = ['short' => [215.9, 279.4], 'long' => [215.9, 330.2], 'a4' => [210, 297]];

    public function __construct(private IdAssets $assets, private IdCredential $credentials, private IdOfficials $officials, private IdCardAddress $addresses) {}

    public function data(SeniorCitizen $senior, bool $draft = false): array
    {
        abort_if(in_array($senior->status, [SeniorCitizenStatus::Deceased, SeniorCitizenStatus::Transferred], true) || $senior->died_on || $senior->death_declared_at, 422, 'A deceased or transferred record cannot be printed.');
        if (! $draft) {
            abort_unless($senior->id_generated_at && $senior->osca_id_number && $senior->verified_at
                && in_array($senior->status, [SeniorCitizenStatus::Verified, SeniorCitizenStatus::Archived], true), 422, 'Verify the record and issue its ID first.');
        }
        $credential = SeniorCitizenIdCredential::query()->where('senior_citizen_id', $senior->id)->first();
        abort_unless($draft || $credential, 422, 'Enable the permanent QR credential before printing this existing ID.');
        $officials = $this->officials->values();
        $senior->loadMissing('barangay');

        return [
            'senior' => $senior, 'draft' => $draft,
            'address' => $this->addresses->format($senior->address, $senior->barangay->name),
            'photo' => null,
            'signature' => null,
            'qr' => $credential ? 'data:image/svg+xml;base64,'.base64_encode($this->credentials->svg($credential)) : null,
            'officials' => $officials,
            'seal' => $this->assets->municipalSealDataUri($officials['municipal_seal'] ?? null),
            'headSignature' => $this->assets->pathDataUri($officials['osca_head_signature'] ?? null),
            'mayorSignature' => $this->assets->pathDataUri($officials['municipal_mayor_signature'] ?? null),
            'printedAt' => now(), 'age' => (int) $senior->birth_date->diffInYears(today()),
        ];
    }

    public function pdf(array $data, string $paper): string
    {
        return $this->sheetPdf([$data], $paper);
    }

    public function sheetPdf(array $cards, string $paper): string
    {
        abort_unless(count($cards) >= 1 && count($cards) <= 4, 422, 'Select one to four senior citizen IDs per sheet.');
        foreach ($cards as $data) {
            if (! $data['address']['fits']) {
                throw ValidationException::withMessages(['address' => 'The address is too long to fit legibly on the ID. Review the address in the senior record before printing.']);
            }
            if (blank($data['officials']['osca_head_name'] ?? null) || blank($data['officials']['municipal_mayor_name'] ?? null) || ! $data['seal']) {
                throw ValidationException::withMessages(['officials' => 'Configure the OSCA Head, Municipal Mayor and official municipal seal before printing.']);
            }
            if (! function_exists('imagecreatefrompng') && collect(['photo', 'signature', 'seal', 'headSignature', 'mayorSignature'])
                ->contains(fn ($key) => str_starts_with($data[$key] ?? '', 'data:image/png'))) {
                throw ValidationException::withMessages(['images' => 'Prepare PNG images through the image editor before printing, or enable PHP GD for existing PNG assets.']);
            }
        }
        $options = new Options;
        $options->setIsRemoteEnabled(false);
        $options->setIsPhpEnabled(false);
        $options->setChroot(storage_path('app'));
        $options->setTempDir(storage_path('framework/cache'));
        $options->setFontCache(storage_path('framework/cache'));
        $pdf = new Dompdf($options);
        [$width, $height] = self::PAPERS[$paper];
        $pdf->setPaper([0, 0, $width * 72 / 25.4, $height * 72 / 25.4]);
        $pdf->loadHtml(view('senior-citizens.ids.pdf', ['cards' => $cards])->render());
        $pdf->render();

        return $pdf->output();
    }
}
