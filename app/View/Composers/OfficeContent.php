<?php

namespace App\View\Composers;

use App\Models\SystemConfiguration;
use App\Services\OfficeSettings;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class OfficeContent
{
    public function __construct(private OfficeSettings $settings) {}

    public function compose(View $view): void
    {
        $request = request();
        if (! $request->attributes->has('office_details')) {
            try {
                $values = $this->settings->values();
            } catch (QueryException $exception) {
                try {
                    Log::warning('Office settings are unavailable.', ['failure_type' => $exception::class]);
                } catch (Throwable) {
                    error_log('Office settings and application logging are unavailable.');
                }
                $values = array_fill_keys(SystemConfiguration::OFFICE_KEYS, null);
            }
            $request->attributes->set('office_details', $values);
        }

        $view->with('officeDetails', $request->attributes->get('office_details'));
    }
}
