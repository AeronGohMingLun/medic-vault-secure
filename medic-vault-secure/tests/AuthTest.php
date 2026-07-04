<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    // ── mb_strlen boundary tests ──────────────────────────────────────────────

    /**
     * 200 × "é" = 200 characters, but 400 UTF-8 bytes.
     *
     * The original strlen()-based check treated this as 400 bytes and would
     * have REJECTED a perfectly valid key (400 > 256).
     * mb_strlen() correctly counts 200 characters and accepts it.
     */
    public function testValidMultiByteKeyUnder256CharsIsAccepted(): void
    {
        $key = str_repeat('é', 200);          // 200 chars, 400 bytes
        $this->assertSame(400, strlen($key),   'Sanity: strlen() counts bytes, not characters.');
        $this->assertTrue(
            validateKeyLength($key),
            'A 200-character key must be accepted; strlen() would wrongly reject it at 400 bytes.'
        );
    }

    /**
     * 256 × "é" = 256 characters, 512 bytes — sits exactly on the limit.
     *
     * strlen() = 512 → old code: 512 > 256 = true → INCORRECTLY rejected.
     * mb_strlen() = 256 → new code: 256 > 256 = false → correctly accepted.
     */
    public function testExactLimitMultiByteKeyIsAccepted(): void
    {
        $key = str_repeat('é', 256);          // 256 chars, 512 bytes
        $this->assertTrue(
            validateKeyLength($key),
            'A key of exactly 256 characters must be accepted (boundary value).'
        );
    }

    /**
     * 257 characters in any encoding exceeds the limit — must be rejected
     * regardless of byte count.
     */
    public function testKeyOver256CharsIsRejected(): void
    {
        $key = str_repeat('é', 257);          // 257 chars, 514 bytes
        $this->assertFalse(
            validateKeyLength($key),
            'A 257-character key must be rejected.'
        );
    }

    // ── Argon2id password_verify tests ───────────────────────────────────────

    public function testVerifySucceedsForCorrectKey(): void
    {
        $plainKey = 'correct_horse_battery_staple_42!';
        $hash     = password_hash($plainKey, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost'   => 4,
            'threads'     => 2,
        ]);
        $this->assertTrue(
            verifyStaffKey($plainKey, $hash),
            'password_verify() must return true for the correct key.'
        );
    }

    public function testVerifyFailsForWrongKey(): void
    {
        $plainKey = 'correct_horse_battery_staple_42!';
        $hash     = password_hash($plainKey, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost'   => 4,
            'threads'     => 2,
        ]);
        $this->assertFalse(
            verifyStaffKey('wrong_key_entirely', $hash),
            'password_verify() must return false for a wrong key.'
        );
    }
}
