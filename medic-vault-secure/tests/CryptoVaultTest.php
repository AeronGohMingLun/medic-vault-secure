<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class CryptoVaultTest extends TestCase
{
    private string $rawKey;

    protected function setUp(): void
    {
        // Fresh 32-byte key for every test; injected via env var so the
        // production code path (loadVaultKey → getenv) is exercised.
        $this->rawKey = random_bytes(32);
        putenv('VAULT_KEY=' . base64_encode($this->rawKey));
    }

    protected function tearDown(): void
    {
        putenv('VAULT_KEY');   // unset to avoid cross-test contamination
    }

    // ── 1. Round-trip ─────────────────────────────────────────────────────────

    public function testRoundTripReturnsOriginalPlaintext(): void
    {
        $plaintext = 'Patient: Ahmad bin Abdullah — Diagnosis: Hypertension Stage II';
        $this->assertSame($plaintext, decryptVault(encryptVault($plaintext)));
    }

    // ── 2. Tamper detection ───────────────────────────────────────────────────

    /**
     * Flipping one byte inside the GCM authentication tag (bytes 12–27 of the
     * raw bundle) must cause decryptVault() to throw RuntimeException.
     * openssl_decrypt returns false on tag mismatch; we must not let that
     * propagate silently as null or an empty string.
     */
    public function testTamperedPayloadThrowsRuntimeException(): void
    {
        $payload = encryptVault('sensitive patient record');
        $raw     = base64_decode($payload, true);

        // Flip every bit in the first byte of the auth tag (offset 12).
        $tampered = substr_replace($raw, chr(ord($raw[12]) ^ 0xFF), 12, 1);

        $this->expectException(RuntimeException::class);
        decryptVault(base64_encode($tampered));
    }

    // ── 3. IV randomness ──────────────────────────────────────────────────────

    /**
     * Encrypting the same plaintext twice must produce different ciphertexts,
     * proving that a fresh random IV is generated on every call.
     */
    public function testEncryptProducesDifferentCiphertextForSamePlaintext(): void
    {
        $plaintext = 'identical input';
        $enc1 = encryptVault($plaintext);
        $enc2 = encryptVault($plaintext);
        $this->assertNotSame(
            $enc1,
            $enc2,
            'Random IV must produce different ciphertext even for identical plaintext.'
        );
    }
}
