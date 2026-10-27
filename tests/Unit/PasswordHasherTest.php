<?php

namespace Tests\Unit;

use App\Hashing\PasswordHasher;
use App\Rules\PasswordInput;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class PasswordHasherTest extends TestCase
{
    public function test_new_passwords_use_salted_argon2id_and_reject_wrong_passwords(): void
    {
        $hasher = new PasswordHasher(['memory' => 19456, 'time' => 2, 'threads' => 1, 'verify' => true]);
        $password = 'Synthetic-test-password';
        $hash = $hasher->make($password);

        $this->assertSame('argon2id', password_get_info($hash)['algoName']);
        $this->assertNotSame($hash, $hasher->make($password));
        $this->assertTrue($hasher->check($password, $hash));
        $this->assertFalse($hasher->check('Incorrect-test-password', $hash));
        $this->assertFalse($hasher->needsRehash($hash));
    }

    #[TestWith(['sha256'])]
    #[TestWith(['bcrypt'])]
    public function test_legacy_hashes_can_be_verified_and_require_an_upgrade(string $algorithm): void
    {
        $hasher = new PasswordHasher(['verify' => true]);
        $password = 'Synthetic-legacy-password';
        $hash = $algorithm === 'sha256' ? hash('sha256', $password) : password_hash($password, PASSWORD_BCRYPT, ['cost' => 4]);

        $this->assertTrue($hasher->check($password, $hash));
        $this->assertFalse($hasher->check('Incorrect-test-password', $hash));
        $this->assertTrue($hasher->needsRehash($hash));
    }

    #[TestWith(['ascii'])]
    #[TestWith(['multibyte'])]
    #[TestWith(['null'])]
    public function test_unsupported_passwords_are_rejected_without_truncation(string $type): void
    {
        $value = match ($type) {
            'ascii' => str_repeat('a', 129), 'multibyte' => str_repeat("\u{00E9}", 129), default => "Synthetic\0password"
        };
        $failures = [];
        (new PasswordInput)->validate('password', $value, function (string $message) use (&$failures): void {
            $failures[] = $message;
        });

        $this->assertSame(['The :attribute must be at most 128 characters and must not contain null characters.'], $failures);
        $this->assertFalse((new PasswordHasher)->check($value, password_hash(str_repeat('a', 72), PASSWORD_BCRYPT, ['cost' => 4])));
    }

    public function test_argon2id_preserves_long_unicode_passphrases_and_bcrypt_cannot_accept_a_truncated_prefix(): void
    {
        $hasher = new PasswordHasher;
        $long = str_repeat("\u{00E9}", 128);

        $this->assertTrue($hasher->check($long, $hasher->make($long)));
        $this->assertFalse($hasher->check(str_repeat('a', 73), password_hash(str_repeat('a', 72), PASSWORD_BCRYPT, ['cost' => 4])));
    }
}
