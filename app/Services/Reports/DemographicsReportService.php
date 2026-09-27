<?php

namespace App\Services\Reports;

use App\Models\SeniorCitizen;

class DemographicsReportService
{
    public function summary(): array
    {
        return [
            'by_sex' => SeniorCitizen::query()->selectRaw('sex, count(*) as total')->groupBy('sex')->orderBy('sex')->pluck('total', 'sex')->all(),
            'by_status' => SeniorCitizen::query()->selectRaw('status, count(*) as total')->groupBy('status')->orderBy('status')->pluck('total', 'status')->all(),
        ];
    }
}
