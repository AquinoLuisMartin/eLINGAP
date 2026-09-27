<?php

namespace App\Services\Reports;

use App\Models\Application;

class ApplicationReportService
{
    public function summary(): array
    {
        return Application::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->orderBy('status')
            ->pluck('total', 'status')
            ->all();
    }
}
