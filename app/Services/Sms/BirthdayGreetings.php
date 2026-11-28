<?php

namespace App\Services\Sms;

use App\Enums\SeniorCitizenStatus;
use App\Models\SeniorCitizen;
use App\Models\SmsMessage;
use App\Models\SmsTemplate;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BirthdayGreetings
{
    public const TEMPLATE = 'Birthday Greeting';

    private const DEFAULT_BODY = 'Maligayang Kaarawan po, [Senior Name]! Mula sa OSCA Santa Maria, binabati po namin kayo ng malusog at masayang kaarawan.';

    public function alerts(): Collection
    {
        $today = today()->toImmutable();
        $dates = $this->dates($today);
        $seniors = SeniorCitizen::query()
            ->where('status', SeniorCitizenStatus::Verified)
            ->whereNull('died_on')->whereNull('death_declared_at')->where('birth_date', '<=', $today->toDateString())
            ->where(function (Builder $query) use ($dates): void {
                foreach ($dates as $date) {
                    $query->orWhere(fn (Builder $match) => $match->whereMonth('birth_date', $date->month)->whereDay('birth_date', $date->day));
                }
            })
            ->with('barangay:id,name')->orderBy('last_name')->orderBy('first_name')->orderBy('id')
            ->get(['id', 'barangay_id', 'first_name', 'middle_name', 'last_name', 'name_suffix', 'birth_date', 'osca_id_number', 'contact_number']);

        if ($seniors->isEmpty()) {
            return collect();
        }

        $greetings = SmsMessage::query()->whereIntegerInRaw('senior_citizen_id', $seniors->modelKeys())
            ->whereIn('birthday_year', $dates->pluck('year')->unique()->all())
            ->get(['senior_citizen_id', 'birthday_year', 'status'])
            ->keyBy(fn (SmsMessage $message) => $message->senior_citizen_id.':'.$message->birthday_year);

        return $seniors->toBase()->map(function (SeniorCitizen $senior) use ($dates, $today, $greetings): array {
            $date = $dates->get($senior->birth_date->format('m-d'));

            return [
                'senior' => $senior,
                'days' => (int) $today->diffInDays($date),
                'date' => $date->toDateString(),
                'age' => $date->year - $senior->birth_date->year,
                'status' => $greetings->get($senior->id.':'.$date->year)?->status,
                'has_mobile' => SmsText::mobile($senior->contact_number) !== null,
            ];
        })->sortBy('days')->values();
    }

    public function upcomingDate(?SeniorCitizen $senior): ?CarbonImmutable
    {
        $today = today()->toImmutable();
        if ($senior?->status !== SeniorCitizenStatus::Verified || $senior->died_on || $senior->death_declared_at
            || ! $senior->birth_date || $senior->birth_date->gt($today)) {
            return null;
        }

        return $this->dates($today)->get($senior->birth_date->format('m-d'));
    }

    public function draft(SeniorCitizen $senior): array
    {
        $date = $this->upcomingDate($senior);
        abort_unless($date, 409, 'This birthday is outside the greeting window.');
        $number = SmsText::mobile($senior->contact_number);
        abort_unless($number, 422, 'This senior has no valid registered mobile number.');
        abort_if(SmsMessage::query()->whereBelongsTo($senior)->where('birthday_year', $date->year)->exists(),
            409, 'A greeting already exists for this birthday.');
        $template = SmsTemplate::query()->where('name', self::TEMPLATE)->where('is_active', true)->whereNull('superseded_by_id')->first()
            ?? new SmsTemplate(['name' => self::TEMPLATE, 'body' => self::DEFAULT_BODY]);

        return ['year' => $date->year, 'number' => $number, 'template' => $template];
    }

    public function queue(SeniorCitizen $senior, int $year, string $number, string $message, int $staffId): void
    {
        DB::transaction(function () use ($senior, $year, $number, $message, $staffId): void {
            $senior = SeniorCitizen::query()->lockForUpdate()->findOrFail($senior->id);
            $draft = $this->draft($senior);
            abort_unless($draft['year'] === $year && $draft['number'] === SmsText::mobile($number),
                409, 'Reopen the birthday greeting to confirm the recipient.');

            app(SmsService::class)->queue($draft['number'], $message, $staffId, $senior->id,
                birthdayYear: $year, templateId: $draft['template']->getKey());
        });
    }

    private function dates(CarbonImmutable $today): Collection
    {
        return collect(range(0, 7))->map(fn (int $days) => $today->addDays($days))
            ->keyBy(fn (CarbonImmutable $date) => $date->format('m-d'));
    }
}
