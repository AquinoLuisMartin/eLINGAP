<?php

namespace Tests\Feature\Sms;

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

    #[TestWith(['/messaging'])]
    #[TestWith(['/sms/blasts'])]
    #[TestWith(['/sms/blasts/create'])]
    public function test_broadcast_views_require_authentication_in_production(string $path): void
    {
        $this->app->instance('env', 'production');

        $this->get($path)->assertRedirect(route('login'));
    }
}
