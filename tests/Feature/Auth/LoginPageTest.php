<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class LoginPageTest extends TestCase
{
    public function test_login_page_displays_the_sign_in_form(): void
    {
        $this->get(route('login'))
            ->assertSee('id="login-form"', false)
            ->assertSee('action="'.route('login').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="email" type="text"', false)
            ->assertSee('name="password" type="password"', false)
            ->assertDontSee('fixed inset-0 z-50 hidden bg-slate-900/50', false);
    }
}
