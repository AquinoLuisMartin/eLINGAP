<?php

namespace App\Services\SeniorCitizens;

use App\Enums\SeniorCitizenStatus;
use App\Models\Barangay;
use App\Models\SeniorCitizen;
use App\Models\SeniorCitizenTransfer;
use App\Services\Benefits\BenefitAudit;
use App\Services\OfficeSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SeniorTransfer
{
    public function __construct(private OfficeSettings $settings) {}

    public function record(SeniorCitizen $senior, array $data): SeniorCitizenTransfer
    {
        Gate::authorize('transfer', $senior);
        $office = $this->settings->requireLocality();

        return DB::transaction(function () use ($senior, $data, $office) {
            $senior = SeniorCitizen::query()->lockForUpdate()->findOrFail($senior->id);
            Gate::authorize('transfer', $senior);
            $last = $senior->transfers()->latest('id')->first();
            if ($last && $data['transferred_on'] < $last->transferred_on->toDateString()) {
                throw ValidationException::withMessages(['transferred_on' => 'The transfer date cannot precede the previous transfer.']);
            }
            $external = $data['type'] === 'EXTERNAL';
            $barangay = $external ? null : Barangay::query()->where('is_active', true)->findOrFail($data['barangay_id']);
            $transfer = $senior->transfers()->create([
                ...collect($data)->except('barangay_id')->all(),
                'previous_location' => implode(', ', [$senior->address, $senior->barangay->name, $office['municipality'], $office['province']]),
                'requester_name' => $data['requested_by'] === 'SELF' ? $senior->full_name : $data['requester_name'],
                'destination_municipality' => $external ? $data['destination_municipality'] : $office['municipality'],
                'destination_province' => $external ? $data['destination_province'] : $office['province'],
                'destination_barangay' => $external ? $data['destination_barangay'] : $barangay->name,
                'senior_name' => $senior->full_name, 'registration_number' => $senior->registration_number,
                'osca_id_number' => $senior->osca_id_number, 'recorded_by' => auth()->id(),
            ]);
            if ($external) {
                $senior->update(['status' => SeniorCitizenStatus::Transferred]);
                $senior->activeAssignments()->update(['status' => 'INACTIVE']);
            } else {
                $senior->update(['barangay_id' => $barangay->id, 'address' => $data['destination_address']]);
            }
            $senior->histories()->create(['changed_by' => auth()->id(), 'action' => 'transferred', 'changes' => ['transfer_id' => $transfer->id, 'type' => $transfer->type]]);
            BenefitAudit::record('senior_citizen.transferred', $senior, ['transfer_id' => $transfer->id, 'type' => $transfer->type]);

            return $transfer;
        });
    }
}
