<?php

namespace Tests\Feature\SeniorCitizens;

use App\Models\BenefitEntitlement;
use App\Models\SeniorCitizen;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class MilestoneIndicatorTest extends TestCase
{
    #[TestWith([80])]
    #[TestWith([85])]
    #[TestWith([90])]
    #[TestWith([95])]
    #[TestWith([100])]
    public function test_reached_milestones_distinguish_the_matching_claim(int $age): void
    {
        $this->travelTo(now()->setDate(2026, 10, 29));
        $senior = new SeniorCitizen(['birth_date' => today()->subYears($age), 'status' => 'VERIFIED']);
        $senior->setRelation('milestoneClaims', collect());

        $this->assertSame(['age' => $age, 'claimed' => false], $senior->milestoneIndicator());
        $this->assertStringContainsString('Milestone Reached - Unclaimed', Blade::render('<x-milestone-badge :senior="$senior" />', compact('senior')));

        $senior->setRelation('milestoneClaims', collect([new BenefitEntitlement(['milestone_age' => $age])]));

        $this->assertSame(['age' => $age, 'claimed' => true], $senior->milestoneIndicator());
        $this->assertStringContainsString('Milestone Claimed', Blade::render('<x-milestone-badge :senior="$senior" />', compact('senior')));
    }

    public function test_an_upcoming_birthday_and_an_older_claim_do_not_complete_the_next_milestone(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 29));
        $senior = new SeniorCitizen(['birth_date' => '1941-10-30', 'status' => 'VERIFIED']);
        $senior->setRelation('milestoneClaims', collect([new BenefitEntitlement(['milestone_age' => 80])]));
        $this->assertSame(['age' => 80, 'claimed' => true], $senior->milestoneIndicator());

        $this->travelTo(now()->addDay());
        $this->assertSame(['age' => 85, 'claimed' => false], $senior->milestoneIndicator());
        $senior->status = 'DECEASED';
        $this->assertNull($senior->milestoneIndicator());
    }
}
