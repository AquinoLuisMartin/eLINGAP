<?php

namespace App\View\Composers;

use App\Models\Program;
use App\Models\SystemConfiguration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class PublicContent
{
    public const OFFICE_KEYS = ['organization_name', 'office_name', 'support_email', 'support_phone', 'office_address', 'office_hours'];

    public const OFFICE_DEFAULTS = [
        'office_address' => 'Santa Maria Municipal Hall, J.C. De Jesus St. (also mapped via R Mercado Street), Poblacion, Santa Maria, 3020 Bulacan',
        'support_phone' => '+63 44 288 2604',
        'office_hours' => '8:00 AM – 5:00 PM',
    ];

    public function compose(View $view): void
    {
        $programs = collect();
        $office = self::OFFICE_DEFAULTS;

        try {
            $programs = Program::query()->whereIn('status', ['ACTIVE', 'UPCOMING'])
                ->where(fn ($query) => $query->whereNull('benefit_code')->orWhere('benefit_code', 'not like', 'synthetic-%'))
                ->orderByRaw("CASE WHEN status = 'ACTIVE' THEN 0 ELSE 1 END")->orderBy('name')
                ->get(['id', 'name']);
            $office = array_replace($office, SystemConfiguration::query()->whereIn('key', self::OFFICE_KEYS)
                ->whereNotNull('value')->where('value', '!=', '')->pluck('value', 'key')->all());
        } catch (QueryException $exception) {
            $programs = collect();
            try {
                Log::warning('Public content is unavailable.', ['failure_type' => $exception::class]);
            } catch (Throwable) {
                error_log('Public content and application logging are unavailable.');
            }
        }

        $view->with([
            'publicPrograms' => $programs,
            'publicOffice' => $office,
        ]);
    }
}
