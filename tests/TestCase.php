<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function be(Authenticatable $user, $guard = null)
    {
        parent::be($user, $guard);
        session()->put('credential_version', $user->credential_version);

        return $this;
    }
}
