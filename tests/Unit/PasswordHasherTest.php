<?php

namespace Tests\Unit;

use App\Hashing\PasswordHasher;
use App\Rules\PasswordInput;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class PasswordHasherTest extends TestCase
{
    public function test_new_passwords_use_salted_bcrypt_and_reject_wrong_passwords(): void
    {
        $hasher = new PasswordHasher(['rounds' => 4, 'verify' => true, 'limit' => 72]);
        $password = 'Synthetic-test-password';
        $hash = $hasher->make($password);

        $this->assertSame('bcrypt', password_get_info($hash)['algoName']);
        $this->assertNotSame($hash, $hasher->make($password));
        $this->assertTrue($hasher->check($password, $hash));
        $this->assertFalse($hasher->check('Incorrect-test-password', $hash));
        $this->assertFalse($hasher->needsRehash($hash));
    }

    public function test_legacy_hashes_can_be_verified_and_require_an_upgrade(): void
    {
        $hasher = new PasswordHasher(['rounds' => 4, 'verify' => true]);
        $password = 'Synthetic-legacy-password';
        $hash = hash('sha256', $password);

        $this->assertTrue($hasher->check($password, $hash));
        $this->assertFalse($hasher->check('Incorrect-test-password', $hash));
        $this->assertTrue($hasher->needsRehash($hash));
        $this->assertNull($hasher->info($hash)['algo']);
    }

    #[TestWith(['ascii'])]
    #[TestWith(['multibyte'])]
    #[TestWith(['null'])]
    public function test_unsupported_passwords_are_rejected_before_bcrypt(string $type): void
    {
        $value = match ($type) {
            'ascii' => str_repeat('a', 73),
            'multibyte' => str_repeat("\u{00E9}", 37),
            'null' => "Synthetic\0password",
        };
        $failures = [];
        (new PasswordInput)->validate('password', $value, function (string $message) use (&$failures): void {
            $failures[] = $message;
        });

        $this->assertSame(['The :attribute must be at most 72 bytes and must not contain null characters.'], $failures);
        $hasher = new PasswordHasher(['rounds' => 4]);
        $this->assertFalse($hasher->check($value, $hasher->make(str_repeat('a', 72))));
    }
}
