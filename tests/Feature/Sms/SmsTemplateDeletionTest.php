<?php

namespace Tests\Feature\Sms;

use App\Models\Barangay;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Models\SmsMessage;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Services\ReferenceData;
use Database\Seeders\BenefitProgramSeeder;
use Database\Seeders\ProgramSmsTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsTemplateDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_delete_a_template_without_losing_history_or_restoring_retired_templates(): void
    {
        $this->seed(BenefitProgramSeeder::class);
        $this->seed(ProgramSmsTemplateSeeder::class);
        $program = Program::where('benefit_code', 'december-assistance')->sole();
        $template = SmsTemplate::where('program_id', $program->id)->sole();
        $retired = SmsTemplate::create([
            'name' => 'Synthetic retired reminder', 'body' => 'Older notice', 'superseded_by_id' => $template->id,
        ]);
        $message = SmsMessage::create([
            'sms_template_id' => $template->id, 'recipient_number' => '+639000000001', 'message' => $template->body, 'status' => 'SENT',
        ]);
        $this->actingAs(User::factory()->create());

        $this->get(route('sms.templates.index'))->assertOk()->assertSee('data-confirm-label="Delete"', false);
        $this->assertNotSoftDeleted($template);
        $this->delete(route('sms.templates.destroy', $template))->assertRedirect(route('sms.templates.index'))
            ->assertSessionHas('status', 'SMS template deleted.');
        $this->assertSoftDeleted($template);

        $this->seed(ProgramSmsTemplateSeeder::class);
        $this->get(route('sms.templates.index'))->assertOk()->assertSee('SMS template deleted.')
            ->assertDontSee($template->name)->assertDontSee($retired->name);
        $broadcast = $this->get(route('sms.blasts.create'))->assertOk()->assertDontSee($retired->name);
        $this->assertFalse($broadcast->viewData('templates')->contains('id', $template->id));
        $this->assertContains((string) $template->id, $broadcast->viewData('retiredTemplates'));
        $this->assertSame($template->id, $message->fresh()->template->id);
        $this->assertSame($template->body, $message->fresh()->message);
        $this->assertNotContains($template->name, array_column(app(ReferenceData::class)->snapshot()['sms_templates'], 'name'));

        $this->post(route('sms.templates.store'), ['name' => $template->name, 'body' => 'Replacement notice', 'is_active' => true])
            ->assertRedirect(route('sms.templates.index'))->assertSessionHasNoErrors();
        $this->assertSame('Replacement notice', SmsTemplate::where('name', $template->name)->sole()->body);
    }

    public function test_a_guest_cannot_delete_an_sms_template(): void
    {
        $template = SmsTemplate::create(['name' => 'Synthetic reminder', 'body' => 'Text']);

        $this->delete(route('sms.templates.destroy', $template))->assertRedirect(route('login'));
        $this->assertNotSoftDeleted($template);
    }

    public function test_deleting_the_birthday_template_restores_the_default_greeting_for_staff(): void
    {
        $this->travelTo(now()->setDate(2026, 12, 29));
        $barangay = Barangay::create(['name' => 'Synthetic barangay', 'code' => 'SYN']);
        $senior = SeniorCitizen::create([
            'barangay_id' => $barangay->id, 'first_name' => 'Synthetic', 'last_name' => 'Senior', 'birth_date' => '1945-12-29',
            'sex' => 'FEMALE', 'address' => 'Synthetic address', 'contact_number' => '+639000000001', 'status' => 'VERIFIED',
        ]);
        $template = SmsTemplate::where('name', 'Birthday Greeting')->sole();
        $template->update(['body' => 'Deleted custom greeting']);
        $this->actingAs(User::factory()->create());

        $this->delete(route('sms.templates.destroy', $template))->assertRedirect();
        $this->get(route('sms.messages.birthday', $senior))->assertOk()
            ->assertSee('Maligayang Kaarawan po, Synthetic Senior!')->assertDontSee('Deleted custom greeting');
        $this->post(route('sms.messages.birthday.store', $senior), [
            'birthday_year' => 2026, 'recipient_number' => '900-000-0001', 'message' => 'Synthetic greeting',
        ])->assertRedirect(route('sms.messages.index'));
        $this->assertDatabaseHas('sms_messages', ['birthday_year' => 2026, 'senior_citizen_id' => $senior->id, 'sms_template_id' => null]);
    }
}
