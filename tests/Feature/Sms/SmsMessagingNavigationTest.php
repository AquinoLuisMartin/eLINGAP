<?php

namespace Tests\Feature\Sms;

use App\Models\SmsTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class SmsMessagingNavigationTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['testing'])]
    #[TestWith(['production'])]
    public function test_messaging_opens_broadcast_form_with_history_link(string $environment): void
    {
        $user = User::factory()->create();
        $this->app->instance('env', $environment);

        $this->actingAs($user)->get('/messaging')
            ->assertViewIs('sms.blasts.create')
            ->assertSee('New SMS broadcast')
            ->assertSee('href="'.route('sms.blasts.index').'" class="secondary-button">View SMS History</a>', false)
            ->assertSee('action="'.route('sms.blasts.store').'"', false);
        $this->assertDatabaseCount('sms_blasts', 0);
        $this->assertDatabaseCount('sms_messages', 0);
    }

    public function test_history_back_link_returns_to_default_broadcast_form(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('sms.blasts.index'))
            ->assertViewIs('sms.blasts.index')
            ->assertSee('SMS history')
            ->assertSee('href="'.route('sms.blasts.create').'">&larr; Back</a>', false)
            ->assertDontSee('New broadcast');
        $this->get(route('sms.blasts.create'))->assertViewIs('sms.blasts.create')->assertSee('View SMS History');
        $this->assertSame(url('/messaging'), route('sms.blasts.create'));
    }

    public function test_existing_broadcast_create_url_still_opens_the_form(): void
    {
        $this->actingAs(User::factory()->create())->get('/sms/blasts/create')
            ->assertViewIs('sms.blasts.create')->assertSee('View SMS History');
    }

    public function test_broadcast_composer_renders_local_template_editor_and_program_preview_without_backend_fields(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('sms.blasts.create'));

        $response->assertDontSee('-- Select a template or create new --')->assertDontSee('Update audience')->assertSee('+ Create new template...')
            ->assertSee('Template Title')->assertSee('Message Body')->assertSee('(Insert Venue/Place)')
            ->assertSee('(Insert Date)')->assertSee('(Insert Time)')->assertSee('Benefit / Pension Program')
            ->assertSee('SSS Pension')->assertSee('GSIS Pension')->assertSee('LGU Social Pension')->assertSee('Centenarian Cash Gift');
        $document = new \DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        $program = $xpath->query('//select[@id="broadcast-program"]')->item(0);
        $this->assertSame('', $program->getAttribute('name'));
        $textarea = $xpath->query('//textarea[@id="broadcast-message"]')->item(0);
        $this->assertSame('Write a message', $textarea->getAttribute('placeholder'));
        $this->assertSame('', $textarea->textContent);
        $this->assertCount(1, $xpath->query('//dialog[@aria-labelledby="new-template-title"]'));
        $this->assertDatabaseCount('sms_templates', 0);
        $this->assertDatabaseCount('sms_messages', 0);
    }

    public function test_templates_expose_escaped_editor_data_without_a_backend_save_form(): void
    {
        $template = SmsTemplate::create([
            'name' => '<script>synthetic title</script>', 'body' => '<img src=x onerror=alert(1)>', 'is_active' => false,
        ]);

        $response = $this->actingAs(User::factory()->create())->get(route('sms.templates.index'));

        $response->assertSee('Edit template')->assertSee('Save changes')
            ->assertDontSee($template->name, false)->assertDontSee($template->body, false);
        $document = new \DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        $data = json_decode($xpath->query('//*[@data-templates]')->item(0)->getAttribute('data-templates'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([
            ['id' => (string) $template->id, 'title' => $template->name, 'body' => $template->body, 'active' => false],
        ], $data);
        $this->assertCount(0, $xpath->query('//dialog//form[@action]'));
    }

    #[TestWith(['/messaging'])]
    #[TestWith(['/sms/blasts'])]
    #[TestWith(['/sms/blasts/create'])]
    public function test_broadcast_views_require_authentication_in_production(string $path): void
    {
        $this->app->instance('env', 'production');

        $this->get($path)->assertRedirect(route('login'));
    }
}
