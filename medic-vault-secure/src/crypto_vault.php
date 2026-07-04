<?php
declare(strict_types=1);

/**
 * Loads and validates the 32-byte AES key from the VAULT_KEY env var.
 * The env var must be the base64 encoding of exactly 32 random bytes.
 */
function loadVaultKey(): string
{
    $b64 = getenv('VAULT_KEY');
    if ($b64 === false || $b64 === '') {
        throw new RuntimeException('VAULT_KEY environment variable is not set.');
    }
    $key = base64_decode($b64, true);
    if ($key === false || strlen($key) !== 32) {
        throw new RuntimeException(
            'VAULT_KEY must base64-decode to exactly 32 bytes. ' .
            'Generate one with: php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"'
        );
    }
    return $key;
}

/**
 * Encrypts $plaintext with AES-256-GCM.
 *
 * Serialises the result as base64( iv[12] · tag[16] · ciphertext ).
 * A fresh random 12-byte IV is generated on every call, guaranteeing that
 * identical plaintexts produce different ciphertexts.
 *
 * @param string      $plaintext Arbitrary data to protect.
 * @param string|null $key       32-byte raw key; falls back to VAULT_KEY env var.
 */
function encryptVault(string $plaintext, ?string $key = null): string
{
    $key ??= loadVaultKey();
    $iv    = random_bytes(12);
    $tag   = '';

    $cipher = openssl_encrypt(
        $plaintext,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag,
        '',
        16
    );

    if ($cipher === false) {
        throw new RuntimeException('openssl_encrypt failed.');
    }

    // Pack: iv (12) | tag (16) | ciphertext, then base64 for safe transport.
    return base64_encode($iv . $tag . $cipher);
}

/**
 * Decrypts a payload produced by encryptVault().
 *
 * Throws RuntimeException when the GCM authentication tag does not match,
 * preventing silent acceptance of tampered or corrupted data.
 *
 * @param string      $payload Base64-encoded iv·tag·ciphertext bundle.
 * @param string|null $key     32-byte raw key; falls back to VAULT_KEY env var.
 */
function decryptVault(string $payload, ?string $key = null): string
{
    $key ??= loadVaultKey();
    $raw   = base64_decode($payload, true);

    // Minimum: 12 bytes IV + 16 bytes tag = 28 bytes (zero-length ciphertext is valid).
    if ($raw === false || strlen($raw) < 28) {
        throw new RuntimeException('Malformed payload: too short to contain IV and tag.');
    }

    $iv         = substr($raw, 0, 12);
    $tag        = substr($raw, 12, 16);
    $ciphertext = substr($raw, 28);

    $plaintext = openssl_decrypt(
        $ciphertext,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    // openssl_decrypt returns false on tag mismatch — never let it propagate silently.
    if ($plaintext === false) {
        throw new RuntimeException(
            'Decryption failed: GCM authentication tag mismatch. Data may be tampered or corrupted.'
        );
    }

    return $plaintext;
}
