<?php

namespace Database\Seeders;

use App\Models\Program;
use Illuminate\Database\Seeder;

class BenefitProgramSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            ['december-assistance', 'Annual December Senior Assistance', 'LGU', 500, ['cadence' => 'ANNUAL', 'cutoff' => '06-30', 'payout_month' => 12], true],
            ['lgu-age-90', 'LGU Age 90 Cash Gift', 'LGU', 5000, ['cadence' => 'MILESTONE', 'milestones' => [90 => 5000]], false],
            ['lgu-age-100', 'LGU Age 100 Cash Gift', 'LGU', 10000, ['cadence' => 'MILESTONE', 'milestones' => [100 => 10000]], false],
            ['national-social-pension', 'National Social Pension', 'NATIONAL', null, ['cadence' => 'QUARTERLY'], false],
            ['local-social-pension', 'Localized Social Pension', 'LGU', 500, ['cadence' => 'SEMIANNUAL'], true],
            ['october-assistance', 'Paglingap kay Lolo at Lola', 'LGU', null, ['cadence' => 'ANNUAL', 'payout_month' => 10, 'october_conditions' => true], false],
            ['provincial-centenarian', 'Provincial Centenarian Cash Gift and Groceries', 'PROVINCIAL', 10000, ['cadence' => 'MILESTONE', 'milestones' => [100 => 10000], 'groceries' => true], false],
            ['national-milestones', 'Expanded Centenarians Act (RA 11982)', 'NATIONAL', null, ['cadence' => 'MILESTONE', 'milestones' => [80 => 10000, 85 => 10000, 90 => 10000, 95 => 10000, 100 => 100000], 'claim_window_months' => 12], false],
            ['tss', 'T.S.S. - Tulong sa Senior', 'LGU', null, ['cadence' => 'ON_DEMAND', 'office_review' => true], true],
        ];
        foreach ($definitions as [$code, $name, $level, $amount, $rules, $provisional]) {
            Program::firstOrCreate(['benefit_code' => $code], ['name' => $name, 'agency' => $level === 'LGU' ? 'OSCA Santa Maria' : $level.' Government', 'government_level' => $level, 'default_amount' => $amount, 'benefit_rules' => $rules, 'provisional' => $provisional, 'status' => 'UPCOMING']);
        }
    }
}
