<?php

namespace App\Services\Reports;

use App\Models\Beneficiary;

class BeneficiaryReportService
{
    public function summary(): array
    {
        return Beneficiary::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->orderBy('status')
            ->pluck('total', 'status')
            ->all();
    }
}
