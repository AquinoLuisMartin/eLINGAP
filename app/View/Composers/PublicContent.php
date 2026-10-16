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

    public function compose(View $view): void
    {
        $programs = collect();
        $office = [];

        try {
            $programs = Program::query()->where('status', 'ACTIVE')->orderBy('name')
                ->get(['id', 'name', 'agency', 'description', 'default_amount']);
            $office = SystemConfiguration::query()->whereIn('key', self::OFFICE_KEYS)
                ->whereNotNull('value')->where('value', '!=', '')->pluck('value', 'key')->all();
        } catch (QueryException $exception) {
            $programs = collect();
            try {
                Log::warning('Public content is unavailable.', ['failure_type' => $exception::class]);
            } catch (Throwable) {
                error_log('Public content and application logging are unavailable.');
            }
        }

        $view->with(['publicPrograms' => $programs, 'publicOffice' => $office]);
    }
}
