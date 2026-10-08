<?php

namespace App\Services\Reports;

use App\Enums\PayoutStatus;
use App\Enums\SeniorCitizenStatus;
use App\Enums\SmsStatus;
use App\Models\Beneficiary;
use App\Models\Payout;
use App\Models\SeniorCitizen;
use App\Models\SmsMessage;

class AdminDashboardReport
{
    public function summary(): array
    {
        $statuses = SeniorCitizen::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $sms = SmsMessage::query()->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $sent = (int) ($sms[SmsStatus::Sent->value] ?? 0);
        $attempted = $sent + (int) ($sms[SmsStatus::Failed->value] ?? 0);

        return [
            'seniors' => (int) $statuses->sum(),
            'verified' => (int) ($statuses[SeniorCitizenStatus::Verified->value] ?? 0),
            'pending' => (int) ($statuses[SeniorCitizenStatus::Pending->value] ?? 0),
            'beneficiaries' => Beneficiary::query()->where('status', 'ACTIVE')->count(),
            'sms_sent' => $sent,
            'sms_attempted' => $attempted,
            'sms_rate' => $attempted > 0 ? round($sent / $attempted * 100, 1) : null,
            'sms_queued' => SmsMessage::query()->where('status', SmsStatus::Queued)->count(),
            'sms_failed' => SmsMessage::query()->where('status', SmsStatus::Failed)->count(),
        ];
    }

    public function distribution(string $period): array
    {
        $rows = Payout::query()->join('payout_schedules', 'payouts.payout_schedule_id', '=', 'payout_schedules.id')
            ->whereBetween('payout_schedules.scheduled_on', [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()])
            ->where('payouts.status', '!=', PayoutStatus::Voided->value)
            ->selectRaw('payout_schedules.scheduled_on, sum(payouts.amount) as planned, sum(case when payouts.status = ? then payouts.amount else 0 end) as released', [PayoutStatus::Released->value])
            ->groupBy('payout_schedules.scheduled_on')->toBase()->get();

        $quarterly = $period === 'Quarterly';
        $buckets = [];
        for ($index = 0; $index < ($quarterly ? 4 : 12); $index++) {
            $date = now()->startOfYear()->addMonths($quarterly ? $index * 3 : $index);
            $buckets[] = ['label' => $quarterly ? 'Q'.($index + 1) : $date->format('M'), 'planned' => 0.0, 'released' => 0.0];
        }

        foreach ($rows as $row) {
            $month = (int) substr($row->scheduled_on, 5, 2) - 1;
            $index = $quarterly ? intdiv($month, 3) : $month;
            $buckets[$index]['planned'] += (float) $row->planned;
            $buckets[$index]['released'] += (float) $row->released;
        }

        $planned = array_sum(array_column($buckets, 'planned'));
        $released = array_sum(array_column($buckets, 'released'));
        $maximum = max(1, ...array_column($buckets, 'planned'));
        $scale = $maximum <= 1 ? 1 : ceil($maximum / 4 / (10 ** floor(log10($maximum / 4)))) * (10 ** floor(log10($maximum / 4))) * 4;

        return ['buckets' => $buckets, 'planned' => $planned, 'released' => $released, 'scale' => $scale, 'completion' => $planned > 0 ? round($released / $planned * 100, 1) : null];
    }
}
