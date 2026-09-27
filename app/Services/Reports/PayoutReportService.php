<?php

namespace App\Services\Reports;

use App\Models\Payout;

class PayoutReportService
{
    public function summary(): array
    {
        return Payout::query()
            ->selectRaw('status, count(*) as total, coalesce(sum(amount), 0) as amount')
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->mapWithKeys(fn ($row): array => [$row->status->value => ['total' => (int) $row->total, 'amount' => (string) $row->amount]])
            ->all();
    }
}
