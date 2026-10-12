<?php

namespace App\Services\Benefits;

use App\Enums\SeniorCitizenStatus;
use App\Models\SeniorCitizen;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class BenefitEligibility
{
    public function reason(SeniorCitizen $senior, array $rules, Carbon $on, ?int $milestone = null): ?string
    {
        if ($senior->status !== SeniorCitizenStatus::Verified || ! $senior->verified_at) {
            return 'The senior record is not verified.';
        }
        if (! $senior->osca_id_number || ! $senior->id_generated_at) {
            return 'OSCA ID issuance has not been recorded.';
        }
        if ($senior->birth_date->copy()->addYears(60)->gt($on)) {
            return 'The senior has not reached age 60.';
        }
        if ($senior->verified_at->copy()->startOfDay()->gt($on) || $senior->id_generated_at->copy()->startOfDay()->gt($on)) {
            return 'Verification and ID issuance must precede eligibility.';
        }
        if ($cutoff = $rules['cutoff'] ?? null) {
            $deadline = Carbon::parse($on->year.'-'.$cutoff, config('app.timezone'))->endOfDay();
            if ($senior->verified_at->gt($deadline)) {
                return 'Verification missed the program cutoff.';
            }
        }
        if ($milestone !== null) {
            $birthday = $senior->birth_date->copy()->addYears($milestone);
            if ($birthday->gt($on)) {
                return 'The milestone age has not been reached.';
            }
            if (isset($rules['claim_window_months']) && $on->gt($birthday->copy()->addMonthsNoOverflow((int) $rules['claim_window_months']))) {
                return 'The national milestone claim window requires staff review.';
            }
        }
        if ($rules['october_conditions'] ?? false) {
            if (! $senior->classification_verified_at || ! $senior->is_bedridden || ! $senior->is_indigent || $senior->has_national_pension || $senior->has_local_pension) {
                return 'Verified bedridden and indigent classifications, with neither pension, are required.';
            }
        }
        if (($rules['office_review'] ?? false) && (! $senior->tss_verified_at || ! $senior->tss_request)) {
            return 'An office request and TSS record verification are required.';
        }

        return null;
    }

    public function periods(array $rules, Carbon $on, string $cycle): array
    {
        $cadence = $rules['cadence'];
        if ($cadence === 'MILESTONE') {
            return array_map(fn ($age) => ['key' => 'AGE-'.$age, 'age' => (int) $age], array_keys($rules['milestones']));
        }
        $valid = match ($cadence) {
            'ANNUAL' => preg_match('/^\d{4}$/', $cycle),
            'QUARTERLY' => preg_match('/^\d{4}-Q[1-4]$/', $cycle),
            'SEMIANNUAL' => preg_match('/^\d{4}-H[12]$/', $cycle),
            'ON_DEMAND' => preg_match('/^\d{4}-[A-Z0-9-]{1,30}$/', $cycle),
            default => false,
        };
        if (! $valid || (int) substr($cycle, 0, 4) > $on->year) {
            throw ValidationException::withMessages(['period' => 'Use a valid benefit period for the program cadence, no later than the eligibility year.']);
        }

        return [['key' => $cycle, 'age' => null]];
    }

    public function periodDate(array $rules, Carbon $on, string $key): Carbon
    {
        if ($rules['cadence'] === 'MILESTONE' || $rules['cadence'] === 'ON_DEMAND') {
            return $on->copy();
        }
        $year = (int) substr($key, 0, 4);
        $month = match ($rules['cadence']) {
            'QUARTERLY' => (int) substr($key, -1) * 3,
            'SEMIANNUAL' => (int) substr($key, -1) * 6,
            default => (int) ($rules['payout_month'] ?? 12),
        };

        return Carbon::create($year, $month, 1)->endOfMonth()->startOfDay()->min($on);
    }
}
