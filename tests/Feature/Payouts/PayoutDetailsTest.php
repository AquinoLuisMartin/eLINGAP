<?php

namespace Tests\Feature\Payouts;

use App\Livewire\Benefits\EntitlementReport;
use App\Livewire\Benefits\SchedulePanel;
use App\Livewire\Benefits\SeniorRecord;
use App\Livewire\Payouts\Details;
use App\Livewire\Payouts\Index;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\BenefitEntitlement;
use App\Models\BenefitPayment;
use App\Models\BenefitSchedule;
use App\Models\BenefitScheduleRecipient;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Models\User;
use App\Services\Benefits\BenefitPayments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\TestCase;

class PayoutDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_references_are_hidden_across_history_schedule_and_reports_without_changing_saved_values(): void
    {
        $this->actingAs(User::factory()->create());
        $entry = $this->entry();
        Livewire::test(Details::class)->call('open', 'benefit', $entry->id)
            ->set('form.identity_checked', true)->call('reviewClaim')->call('confirmClaim');
        $payment = BenefitPayment::sole();
        $payment->update(['external_reference' => 'HISTORICAL-REFERENCE']);
        $originalPayment = $payment->fresh()->getAttributes();
        $audit = AuditLog::where('action', 'benefit.claimed')->sole();
        $originalAudit = $audit->getAttributes();

        $history = Livewire::test(SeniorRecord::class, ['seniorId' => $entry->senior_citizen_id])
            ->assertDontSee('Payment references')->assertDontSee($payment->reference_number)->assertDontSee('HISTORICAL-REFERENCE')
            ->assertSee('Claim Information')->assertSee('Synthetic Recipient');
        $document = new \DOMDocument;
        @$document->loadHTML($history->html());
        $headings = [];
        foreach ((new \DOMXPath($document))->query('//table/thead/tr/th') as $heading) {
            $headings[] = trim($heading->textContent);
        }
        $this->assertSame(['Program', 'Original period', 'Amount', 'Status'], $headings);
        Livewire::test(SchedulePanel::class, ['scheduleId' => $entry->benefit_schedule_id])
            ->assertDontSee($payment->reference_number)->assertDontSee('Payment reference')
            ->assertDontSeeHtml('wire:model="externalReference"');
        Livewire::test(EntitlementReport::class)->assertDontSee($payment->reference_number)->assertDontSee('Payment references');
        $response = app(EntitlementReport::class)->export();
        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();
        $this->assertStringNotContainsString('Payment references', $csv);
        $this->assertStringNotContainsString($payment->reference_number, $csv);
        $this->assertStringContainsString('Claim history', $csv);
        $this->assertSame($originalPayment, $payment->fresh()->getAttributes());
        $this->assertSame($originalAudit, $audit->fresh()->getAttributes());
    }

    #[TestWith([null, 'Not recorded'])]
    #[TestWith([false, 'Not verified'])]
    #[TestWith([true, 'Verified'])]
    public function test_claim_information_has_read_only_fields_and_uses_saved_verification_values(?bool $verified, string $label): void
    {
        $this->actingAs(User::factory()->create());
        $entry = $this->entry();
        $modal = Livewire::test(Details::class)->call('open', 'benefit', $entry->id)
            ->set('form.identity_checked', true)->call('reviewClaim')->call('confirmClaim');
        $payment = BenefitPayment::sole();
        $payment->update(['claimant_type' => 'REPRESENTATIVE', 'claimant_name' => 'Synthetic Proxy', 'claimant_relationship' => 'Child', 'claimant_contact' => '+639170000002', 'identity_verified' => $verified, 'authorization_verified' => $verified]);

        $modal->call('open', 'benefit', $entry->id);

        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$modal->html());
        $xpath = new \DOMXPath($document);
        $section = $xpath->query('//section[@aria-label="Claim Information"]')->item(0);
        $this->assertNotNull($section);
        $this->assertSame('Claimed', trim($xpath->query('.//h3/following-sibling::span', $section)->item(0)->textContent));
        $fields = [];
        foreach ($xpath->query('.//dl/div', $section) as $field) {
            $fields[trim($field->getElementsByTagName('dt')->item(0)->textContent)] = trim($field->getElementsByTagName('dd')->item(0)->textContent);
        }
        $this->assertSame('₱500.00', $fields['Amount released']);
        $this->assertSame('Synthetic Proxy', $fields['Claimant']);
        $this->assertSame('Proxy', $fields['Claim method']);
        $this->assertSame('Child', $fields['Relationship']);
        $this->assertSame('+639170000002', $fields['Contact number']);
        $this->assertSame($label, $fields['Identity verification']);
        $this->assertSame($label, $fields['Authorization verification']);
        $this->assertSame(auth()->user()->full_name, $fields['Processing staff']);
        $this->assertSame(0, $xpath->query('.//input | .//select | .//textarea | .//button', $section)->length);
    }

    public function test_modal_hides_demo_notices_without_changing_saved_data_or_demo_markers(): void
    {
        $this->actingAs(User::factory()->create());
        $entry = $this->entry();
        $modal = Livewire::test(Details::class)->call('open', 'benefit', $entry->id)
            ->set('form.identity_checked', true)->call('reviewClaim')->call('confirmClaim');
        $payment = BenefitPayment::sole();
        $payment->update(['reference_number' => 'DEMO-LOCAL-SYNTHETIC']);
        $entry->schedule->update(['rule_snapshot' => ['local_demo' => true]]);

        $modal->call('open', 'benefit', $entry->id)->assertDontSee('Local demo only');
        app()->instance('env', 'local');
        try {
            $modal->call('open', 'benefit', $entry->id)->assertDontSee('Local demo only')
                ->assertDontSee('No funds released')->assertDontSee('funding are simulated')->assertSee('Claim Information');
            $this->assertSame('DEMO-LOCAL-SYNTHETIC', $payment->fresh()->reference_number);
            $this->assertTrue($entry->schedule->fresh()->rule_snapshot['local_demo']);
            $this->assertSame('CLAIMED', $entry->fresh()->status->value);

            $unclaimed = $entry->replicate();
            $unclaimed->fill(['period_key' => '2026-Q3', 'status' => 'UNCLAIMED', 'claimed_at' => null]);
            $unclaimed->save();
            $modal->call('open', 'benefit', $unclaimed->id)->assertDontSee('Local demo only')
                ->assertDontSee('funding are simulated')->assertSee('Beneficiary & Payout Information')->assertSee('Unclaimed');
            $this->assertSame('UNCLAIMED', $unclaimed->fresh()->status->value);
        } finally {
            app()->instance('env', 'testing');
        }
    }

    public function test_cash_release_uses_one_compact_confirmation_then_returns_to_the_table(): void
    {
        $this->actingAs(User::factory()->create());
        $entry = $this->entry();
        $modal = Livewire::test(Details::class)->call('open', 'benefit', $entry->id)->call('startClaim')
            ->assertSee('Confirm Claim')->assertDontSee('Review Claim')->set('form.identity_checked', true);

        $modal->call('reviewClaim')->assertSee('Confirm Cash Release')
            ->assertSee('Are you sure the cash has been handed to the verified claimant?')
            ->assertDontSee('Beneficiary & Payout Information')->assertDontSee('Claim Information');
        $document = new \DOMDocument;
        @$document->loadHTML($modal->html());
        $xpath = new \DOMXPath($document);
        $this->assertSame(1, $xpath->query('//*[@role="dialog"]')->length);
        $buttons = [];
        foreach ($xpath->query('//*[@role="dialog"]//button') as $button) {
            $buttons[] = trim($button->textContent);
        }
        $this->assertSame(['Cancel', 'Confirm Cash Release'], $buttons);
        $modal->call('cancelReview')->assertSet('review', null)->assertSet('claimFormOpen', true)
            ->assertSeeHtml('wire:submit="reviewClaim"');
        $this->assertDatabaseCount('benefit_payments', 0);

        $modal->call('reviewClaim')->call('confirmClaim')->assertSet('payoutId', null)
            ->assertSee('Claim recorded successfully.')->assertDontSeeHtml('role="dialog"')
            ->assertDispatched('payout-claimed');
        $payment = BenefitPayment::sole();
        $this->assertSame(auth()->id(), $payment->recorded_by);
        $this->assertNotNull($payment->paid_at);
        $this->assertTrue($payment->identity_verified);
        $this->assertSame('CLAIMED', $entry->fresh()->status->value);
        $this->assertDatabaseHas('audit_logs', ['action' => 'benefit.claimed', 'auditable_id' => $entry->id]);
    }

    #[TestWith(['LGU'])]
    #[TestWith(['PROVINCIAL'])]
    #[TestWith(['NATIONAL'])]
    public function test_claims_save_without_a_payment_reference_for_every_government_level(string $level): void
    {
        $this->actingAs(User::factory()->create());
        $entry = $this->entry();
        $entry->program->update(['government_level' => $level]);

        $modal = Livewire::test(Details::class)->call('open', 'benefit', $entry->id)->call('startClaim')
            ->assertDontSee('Payment reference')->assertDontSeeHtml('wire:model="form.external_reference"')
            ->set('form.identity_checked', true)->set('form.external_reference', ['unexpected' => 'removed field'])
            ->call('reviewClaim')->assertHasNoErrors()->assertSee('Confirm Cash Release')->assertDontSee('Payment reference');

        $this->assertArrayNotHasKey('external_reference', $modal->get('review'));
        $modal->call('confirmClaim')->assertSee('Claim recorded successfully.')->assertSet('payoutId', null);
        $payment = BenefitPayment::sole();
        $this->assertNull($payment->external_reference);
        $this->assertTrue($payment->identity_verified);
        $this->assertSame('CLAIMED', $entry->fresh()->status->value);
        $audit = AuditLog::where('action', 'benefit.claimed')->sole();
        $this->assertSame($payment->reference_number, $audit->new_values['reference']);
        $modal->assertDontSee($payment->reference_number);

        $payment->update(['external_reference' => 'HISTORICAL-RECEIPT']);
        $modal->call('open', 'benefit', $entry->id)->assertDontSee('Payment reference')->assertDontSee('HISTORICAL-RECEIPT');
        $this->assertSame('HISTORICAL-RECEIPT', $payment->fresh()->external_reference);
    }

    #[TestWith(['SELF'])]
    #[TestWith(['REPRESENTATIVE'])]
    public function test_ready_unclaimed_payouts_open_a_verified_claim_form_and_save_only_after_confirmation(string $method): void
    {
        $this->actingAs(User::factory()->create());
        $entry = $this->entry();
        $entry->update(['status' => 'UNCLAIMED']);
        $entry->schedule->update(['allow_representative' => true]);
        $entry->seniorCitizen->documents()->create(['document_type' => 'AUTHORIZATION', 'path' => 'synthetic.pdf', 'original_name' => 'synthetic.pdf', 'mime_type' => 'application/pdf', 'size' => 100]);

        $modal = Livewire::test(Details::class)->call('open', 'benefit', $entry->id)
            ->assertViewHas('canClaim', true)->assertSee('Record Claim')->assertDontSeeHtml('wire:submit="reviewClaim"')
            ->call('startClaim')->assertSet('claimFormOpen', true)->assertSeeHtml('wire:submit="reviewClaim"')
            ->call('reviewClaim')->assertHasErrors('form.identity_checked')
            ->set('form.claimant_type', $method)->set('form.claimant_name', 'Synthetic Proxy')->set('form.claimant_relationship', 'Child')
            ->set('form.identity_checked', true)->set('form.authorization_checked', true)
            ->call('reviewClaim')->assertHasNoErrors()->assertSee('Confirm Cash Release');

        $this->assertDatabaseCount('benefit_payments', 0);
        $modal->call('confirmClaim')->assertSee('Claim recorded successfully.')->assertSet('claimFormOpen', false)
            ->assertSet('payoutId', null)->assertDontSeeHtml('wire:submit="reviewClaim"')->assertDontSeeHtml('wire:click="startClaim"');
        $this->assertSame('CLAIMED', $entry->fresh()->status->value);
        $this->assertDatabaseHas('benefit_payments', ['benefit_entitlement_id' => $entry->id, 'claimant_type' => $method, 'recorded_by' => auth()->id(), 'external_reference' => null]);
        $modal->call('startClaim')->assertSee('not approved and funded for release');
        $this->assertDatabaseCount('benefit_payments', 1);
    }

    #[TestWith(['CLOSED', 500])]
    #[TestWith(['DRAFT', 500])]
    #[TestWith(['APPROVED', 0])]
    public function test_unclaimed_payouts_without_an_approved_funded_schedule_remain_view_only(string $status, int $funds): void
    {
        $this->actingAs(User::factory()->create());
        $entry = $this->entry();
        $entry->update(['status' => 'UNCLAIMED']);
        $entry->schedule->update(['status' => $status, 'available_funds' => $funds]);

        Livewire::test(Details::class)->call('open', 'benefit', $entry->id)->assertViewHas('canClaim', false)
            ->assertDontSeeHtml('wire:click="startClaim"')->call('startClaim')->assertSet('claimFormOpen', false);

        $this->assertDatabaseCount('benefit_payments', 0);
    }

    public function test_demo_payouts_cannot_be_claimed_outside_the_local_environment(): void
    {
        $this->actingAs(User::factory()->create());
        $entry = $this->entry();
        $entry->schedule->update(['rule_snapshot' => ['local_demo' => true]]);

        try {
            app(BenefitPayments::class)->claim($entry, ['claimant_type' => 'SELF', 'identity_checked' => true]);
            $this->fail('A demo payout must not be processed outside the local environment.');
        } catch (HttpExceptionInterface $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertDatabaseCount('benefit_payments', 0);
    }

    public function test_identity_verification_is_required_in_the_modal_and_payment_service(): void
    {
        $this->actingAs(User::factory()->create());
        $entry = $this->entry();

        Livewire::test(Details::class)->call('open', 'benefit', $entry->id)
            ->assertSee('No claim has been recorded for this payout.')
            ->call('reviewClaim')->assertHasErrors('form.identity_checked');

        try {
            app(BenefitPayments::class)->claim($entry, ['claimant_type' => 'SELF']);
            $this->fail('The backend must reject a claim without identity verification.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('identity_checked', $exception->errors());
        }
        $this->assertDatabaseCount('benefit_payments', 0);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'benefit.claimed']);
    }

    public function test_proxy_details_and_authorization_are_required_and_saved_from_confirmation(): void
    {
        $this->actingAs(User::factory()->create());
        $entry = $this->entry();
        $entry->schedule->update(['allow_representative' => true]);
        $entry->seniorCitizen->documents()->create(['document_type' => 'AUTHORIZATION', 'path' => 'synthetic.pdf', 'original_name' => 'synthetic.pdf', 'mime_type' => 'application/pdf', 'size' => 100]);
        $modal = Livewire::test(Details::class)->call('open', 'benefit', $entry->id)
            ->set('form.claimant_type', 'REPRESENTATIVE')->set('form.identity_checked', true);

        $modal->call('reviewClaim')->assertHasErrors(['form.claimant_name', 'form.claimant_relationship', 'form.authorization_checked']);
        $modal->set('form.claimant_name', 'Synthetic Proxy')->set('form.claimant_relationship', 'Child')
            ->set('form.authorization_checked', true)->call('reviewClaim')
            ->assertSee('Confirm Cash Release')->assertSee('Cancel')
            ->call('cancelReview')->assertSet('review', null);
        $this->assertDatabaseCount('benefit_payments', 0);
        $modal->call('reviewClaim')->call('confirmClaim')->call('open', 'benefit', $entry->id)->assertSee('Claim Information')
            ->assertSeeInOrder(['Claim method', 'Proxy'])->assertSeeInOrder(['Authorization verification', 'Verified']);

        $payment = BenefitPayment::sole();
        $this->assertSame('Synthetic Proxy', $payment->claimant_name);
        $this->assertSame('Child', $payment->claimant_relationship);
        $this->assertTrue($payment->identity_verified);
        $this->assertTrue($payment->authorization_verified);
        $this->assertNull($entry->seniorCitizen->fresh()->representative_name);
        $audit = AuditLog::where('action', 'benefit.claimed')->sole();
        $this->assertSame($payment->id, $audit->new_values['payment_id']);
        $this->assertSame('REPRESENTATIVE', $audit->new_values['claimant_type']);
        $this->assertTrue($audit->new_values['identity_verified']);
        $this->assertTrue($audit->new_values['authorization_verified']);
        $modal->call('open', 'benefit', $entry->id)->assertDontSeeHtml('wire:submit="reviewClaim"')
            ->assertDontSeeHtml('wire:click="confirmClaim"')->assertSee('Synthetic Proxy');
        Livewire::test(Index::class)->assertViewHas('proxyCount', 1)
            ->assertViewHas('counts', fn ($counts) => $counts['CLAIMED'] === 1);
    }

    public function test_confirmed_claim_saves_a_snapshot_audit_and_shared_history_and_remains_viewable(): void
    {
        $this->travelTo(now()->startOfSecond());
        $staff = User::factory()->create();
        $this->actingAs($staff);
        $entry = $this->entry();

        $modal = Livewire::test(Details::class)->call('open', 'benefit', $entry->id)
            ->assertSee('Payout Details')->assertSee('Synthetic Claim Program')->assertSee('2026-Q4')
            ->set('form.identity_checked', true)->set('form.authorization_checked', true)->call('reviewClaim')->assertSee('Confirm Cash Release');

        $this->assertDatabaseCount('benefit_payments', 0);
        $modal->call('confirmClaim')->assertSee('Claim recorded successfully.')->assertDispatched('payout-claimed');

        $payment = BenefitPayment::sole();
        $this->assertSame('CLAIMED', $entry->fresh()->status->value);
        $this->assertTrue($entry->fresh()->claimed_at->equalTo(now()));
        $this->assertSame($entry->id, $payment->benefit_entitlement_id);
        $this->assertSame('Synthetic Recipient', $payment->claimant_name);
        $this->assertSame('+639170000001', $payment->claimant_contact);
        $this->assertSame($staff->id, $payment->recorded_by);
        $this->assertTrue($payment->paid_at->equalTo(now()));
        $this->assertDatabaseHas('benefit_schedule_recipients', ['benefit_entitlement_id' => $entry->id, 'outcome' => 'CLAIMED']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'benefit.claimed', 'auditable_id' => $entry->id]);
        Livewire::test(Index::class)->assertViewHas('counts', fn ($counts) => $counts['CLAIMED'] === 1)->assertSee('RECIPIENT, SYNTHETIC');
        Livewire::test(SeniorRecord::class, ['seniorId' => $entry->senior_citizen_id])
            ->assertSee('Claimed')->assertDontSee($payment->reference_number)->assertSee($staff->full_name);

        $entry->seniorCitizen->update(['first_name' => 'Changed']);
        $modal->call('open', 'benefit', $entry->id)->assertSee('Claim Information')
            ->assertSee('Synthetic Recipient')->assertSee('Claimed')
            ->assertDontSee('Relationship')->assertDontSee('Contact number')->assertDontSee('Authorization verification')
            ->call('confirmClaim')->assertSee('Review and confirm the payout details');
        $this->assertDatabaseCount('benefit_payments', 1);
    }

    public function test_direct_confirmation_and_concurrent_duplicate_claims_cannot_create_a_second_payment(): void
    {
        $this->actingAs(User::factory()->create());
        $entry = $this->entry();
        $first = Livewire::test(Details::class)->call('open', 'benefit', $entry->id);
        $first->call('confirmClaim')->assertSee('Review and confirm the payout details');
        $this->assertDatabaseCount('benefit_payments', 0);
        $first->set('form.identity_checked', true)->set('form.authorization_checked', true)->call('reviewClaim');
        $second = Livewire::test(Details::class)->call('open', 'benefit', $entry->id)->set('form.identity_checked', true)->set('form.authorization_checked', true)->call('reviewClaim');

        $first->call('confirmClaim');
        $second->call('confirmClaim')->assertSee('Claim not saved');

        $this->assertDatabaseCount('benefit_payments', 1);
        $this->assertSame(1, AuditLog::where('action', 'benefit.claimed')->count());
    }

    #[TestWith(['DRAFT', 500, 'UNPAID'])]
    #[TestWith(['APPROVED', 0, 'UNPAID'])]
    #[TestWith(['APPROVED', 500, 'PENDING'])]
    public function test_claim_recording_is_blocked_without_approval_funding_or_recipient_review(string $scheduleStatus, int $funds, string $status): void
    {
        $this->actingAs(User::factory()->create());
        $entry = $this->entry();
        $entry->schedule->update(['status' => $scheduleStatus, 'available_funds' => $funds]);
        $entry->update(['status' => $status]);

        Livewire::test(Details::class)->call('open', 'benefit', $entry->id)
            ->assertViewHas('canClaim', false)->set('form.identity_checked', true)->set('form.authorization_checked', true)->call('reviewClaim')->assertSee('Claim not saved');

        $this->assertDatabaseCount('benefit_payments', 0);
    }

    public function test_changed_funding_between_review_and_confirmation_blocks_payment(): void
    {
        $this->actingAs(User::factory()->create());
        $entry = $this->entry();
        $modal = Livewire::test(Details::class)->call('open', 'benefit', $entry->id)->set('form.identity_checked', true)->set('form.authorization_checked', true)->call('reviewClaim');
        $entry->schedule->update(['available_funds' => 0]);

        $modal->call('confirmClaim')->assertSee('Claim not saved');

        $this->assertDatabaseCount('benefit_payments', 0);
        $this->assertSame('UNPAID', $entry->fresh()->status->value);
    }

    public function test_two_payouts_for_one_senior_are_claimed_independently(): void
    {
        $this->actingAs(User::factory()->create());
        $entry = $this->entry();
        $other = $entry->replicate();
        $other->period_key = '2026-Q3';
        $other->save();
        $entry->schedule->update(['available_funds' => 1000]);

        $modal = Livewire::test(Details::class)->call('open', 'benefit', $entry->id)->set('form.identity_checked', true)->set('form.authorization_checked', true)->call('reviewClaim')->call('confirmClaim');

        $this->assertSame('UNPAID', $other->fresh()->status->value);
        $modal->call('open', 'benefit', $other->id)->set('form.identity_checked', true)->set('form.authorization_checked', true)->call('reviewClaim')->call('confirmClaim');
        $this->assertDatabaseCount('benefit_payments', 2);
        $this->assertSame(2, BenefitPayment::distinct('benefit_entitlement_id')->count('benefit_entitlement_id'));
        $this->assertSame('CLAIMED', $other->fresh()->status->value);
    }

    public function test_claim_failure_rolls_back_payment_status_and_audit_and_shows_an_error(): void
    {
        $this->actingAs(User::factory()->create());
        $entry = $this->entry();
        $modal = Livewire::test(Details::class)->call('open', 'benefit', $entry->id)->set('form.identity_checked', true)->set('form.authorization_checked', true)->call('reviewClaim');
        AuditLog::creating(function (AuditLog $audit): void {
            if ($audit->action === 'benefit.claimed') {
                throw new \RuntimeException('Synthetic audit failure');
            }
        });

        try {
            $modal->call('confirmClaim')->assertSee('The claim could not be saved.');
            $this->assertDatabaseCount('benefit_payments', 0);
            $this->assertSame('UNPAID', $entry->fresh()->status->value);
            $this->assertDatabaseMissing('audit_logs', ['action' => 'benefit.claimed']);
            $this->assertDatabaseHas('benefit_schedule_recipients', ['benefit_entitlement_id' => $entry->id, 'outcome' => 'SELECTED']);
        } finally {
            AuditLog::flushEventListeners();
        }
    }

    public function test_admin_can_view_but_cannot_record_a_claim(): void
    {
        $this->actingAs(User::factory()->create());
        $entry = $this->entry();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(Details::class)->call('open', 'benefit', $entry->id)
            ->assertViewHas('canClaim', false)->set('form.identity_checked', true)->set('form.authorization_checked', true)->call('reviewClaim')->assertForbidden();

        $this->assertDatabaseCount('benefit_payments', 0);
    }

    public function test_representative_claim_requires_proof_and_retains_registered_claimant_details(): void
    {
        $this->actingAs(User::factory()->create());
        $entry = $this->entry();
        $entry->schedule->update(['allow_representative' => true]);
        $entry->seniorCitizen->update(['representative_name' => 'Synthetic Representative', 'representative_relationship' => 'Child', 'representative_contact' => '+639170000002']);
        $modal = Livewire::test(Details::class)->call('open', 'benefit', $entry->id)
            ->set('form.claimant_type', 'REPRESENTATIVE')->set('form.identity_checked', true)->set('form.authorization_checked', true)->call('reviewClaim');

        $modal->call('confirmClaim')->assertSee('recorded authorization');
        $this->assertDatabaseCount('benefit_payments', 0);
        $entry->seniorCitizen->documents()->create(['document_type' => 'AUTHORIZATION', 'path' => 'synthetic.pdf', 'original_name' => 'synthetic.pdf', 'mime_type' => 'application/pdf', 'size' => 100]);
        $modal->set('form.identity_checked', true)->set('form.authorization_checked', true)->call('reviewClaim')->call('confirmClaim')->assertSee('Claim recorded successfully.');

        $this->assertDatabaseHas('benefit_payments', ['benefit_entitlement_id' => $entry->id, 'claimant_type' => 'REPRESENTATIVE', 'claimant_name' => 'Synthetic Representative', 'claimant_relationship' => 'Child', 'claimant_contact' => '+639170000002']);
    }

    public function test_changed_claimant_details_require_a_fresh_confirmation(): void
    {
        $this->actingAs(User::factory()->create());
        $entry = $this->entry();
        $modal = Livewire::test(Details::class)->call('open', 'benefit', $entry->id)->set('form.identity_checked', true)->set('form.authorization_checked', true)->call('reviewClaim');
        $entry->seniorCitizen->update(['first_name' => 'Changed']);

        $modal->call('confirmClaim')->assertSee('The claimant details changed.');

        $this->assertDatabaseCount('benefit_payments', 0);
    }

    public function test_reversed_claims_remain_in_payout_history_when_a_replacement_payment_is_recorded(): void
    {
        $staff = User::factory()->create();
        $this->actingAs($staff);
        $entry = $this->entry();
        $modal = Livewire::test(Details::class)->call('open', 'benefit', $entry->id)->set('form.identity_checked', true)->set('form.authorization_checked', true)->call('reviewClaim')->call('confirmClaim');
        $original = BenefitPayment::sole();
        $this->actingAs(User::factory()->admin()->create());
        app(BenefitPayments::class)->reverse($original, 'Synthetic correction to payment');
        $this->actingAs($staff);

        $modal->call('open', 'benefit', $entry->id)->set('form.identity_checked', true)->set('form.authorization_checked', true)->call('reviewClaim')->call('confirmClaim')
            ->call('open', 'benefit', $entry->id)->assertDontSee($original->reference_number)->assertSee('Reversed claim')->assertSee('Synthetic correction to payment');

        $this->assertDatabaseCount('benefit_payments', 2);
        $this->assertSame(1, BenefitPayment::whereNull('reversed_at')->count());
        $this->assertNotNull($original->fresh()->reversed_at);
        $replacement = BenefitPayment::latest('id')->first();
        $this->assertSame([$replacement->id, $original->id], $modal->viewData('record')->payments->modelKeys());
        $history = Livewire::test(SeniorRecord::class, ['seniorId' => $entry->senior_citizen_id])
            ->assertDontSee($replacement->reference_number)->assertDontSee($original->reference_number)->assertDontSee('Reversed claim')->assertSee('Claimed');
        $this->assertSame([$replacement->id], $history->viewData('entitlements')->first()->payments->modelKeys());
    }

    public function test_announced_requirements_remain_required_while_national_claims_need_no_payment_reference(): void
    {
        $this->actingAs(User::factory()->create());
        $entry = $this->entry();
        $entry->program->update(['government_level' => 'NATIONAL']);
        $entry->schedule->update(['requirements' => 'Synthetic identity document']);
        $modal = Livewire::test(Details::class)->call('open', 'benefit', $entry->id)->set('form.identity_checked', true)->set('form.authorization_checked', true)->call('reviewClaim');

        $modal->call('confirmClaim')->assertSee('Confirm the announced requirements');
        $this->assertDatabaseCount('benefit_payments', 0);
        $modal->set('form.requirements_checked', true)->set('form.identity_checked', true)->set('form.authorization_checked', true)->call('reviewClaim')->call('confirmClaim')
            ->assertSee('Claim recorded successfully.');
        $this->assertDatabaseHas('benefit_payments', ['benefit_entitlement_id' => $entry->id, 'external_reference' => null]);
    }

    private function entry(): BenefitEntitlement
    {
        $program = Program::create(['name' => 'Synthetic Claim Program', 'agency' => 'Synthetic agency', 'status' => 'ACTIVE', 'government_level' => 'LGU']);
        $barangay = Barangay::create(['name' => 'Synthetic area', 'code' => 'SYN']);
        $senior = SeniorCitizen::forceCreate(['barangay_id' => $barangay->id, 'first_name' => 'Synthetic', 'last_name' => 'Recipient', 'birth_date' => '1948-01-01', 'sex' => 'FEMALE', 'address' => 'Synthetic address', 'status' => 'VERIFIED', 'verified_at' => now()->subYear(), 'id_generated_at' => now()->subYear(), 'osca_id_number' => '26-1234', 'contact_number' => '+639170000001']);
        $schedule = BenefitSchedule::create(['program_id' => $program->id, 'reference_number' => 'SYN-CLAIM', 'period_key' => '2026-Q4', 'eligibility_on' => today(), 'scheduled_at' => now()->subHour(), 'location' => 'Synthetic venue', 'barangay_ids' => [], 'rule_snapshot' => [], 'amount' => 500, 'available_funds' => 500, 'status' => 'APPROVED', 'created_by' => auth()->id(), 'approved_by' => auth()->id(), 'approved_at' => now()]);
        $entry = BenefitEntitlement::create(['program_id' => $program->id, 'senior_citizen_id' => $senior->id, 'benefit_schedule_id' => $schedule->id, 'period_key' => '2026-Q4', 'eligibility_on' => today(), 'amount' => 500, 'status' => 'UNPAID', 'reviewed_at' => now(), 'reviewed_by' => auth()->id(), 'rule_snapshot' => []]);
        BenefitScheduleRecipient::create(['benefit_schedule_id' => $schedule->id, 'benefit_entitlement_id' => $entry->id, 'outcome' => 'SELECTED']);

        return $entry;
    }
}
