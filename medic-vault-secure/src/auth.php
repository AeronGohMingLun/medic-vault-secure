<?php
declare(strict_types=1);

/**
 * Returns the Argon2id hash stored for $username, or null if the user is not found.
 * Uses a parameterized query to prevent SQL injection.
 */
function getStoredHashForUser(PDO $pdo, string $username): ?string
{
    $stmt = $pdo->prepare(
        'SELECT auth_key_hash FROM staff_credentials WHERE username = :username LIMIT 1'
    );
    $stmt->bindValue(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row !== false ? (string) $row['auth_key_hash'] : null;
}

/**
 * Returns true iff $key is within the 256-character limit.
 *
 * Fix: mb_strlen() counts Unicode code-points, not raw bytes.
 * The original strlen()-based check would wrongly reject a valid key whose
 * multi-byte UTF-8 encoding exceeded 256 bytes while its character count did not.
 */
function validateKeyLength(string $key): bool
{
    return mb_strlen($key, 'UTF-8') <= 256;
}

/**
 * Constant-time verification of $inputKey against a stored Argon2id hash.
 * Fix: replaces md5() comparison with password_verify(), which is timing-safe
 * and uses a memory-hard algorithm.
 */
function verifyStaffKey(string $inputKey, string $storedHash): bool
{
    return password_verify($inputKey, $storedHash);
}

// ─── HTTP endpoint ─────────────────────────────────────────────────────────────
// Guard: only execute when auth.php is the request entry point, not when it is
// included by composer autoload (e.g. during PHPUnit runs).
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    require_once __DIR__ . '/db_config.php';

    header('Content-Type: application/json; charset=UTF-8');

    $username = isset($_POST['username']) ? trim((string) $_POST['username']) : '';
    $inputKey = isset($_POST['key'])      ? (string) $_POST['key']            : '';

    if ($username === '' || $inputKey === '') {
        http_response_code(400);
        echo json_encode(['error' => 'username and key are required.']);
        exit;
    }

    if (!validateKeyLength($inputKey)) {
        http_response_code(400);
        echo json_encode(['error' => 'Key exceeds maximum length of 256 characters.']);
        exit;
    }

    try {
        $pdo        = getPDO();
        $storedHash = getStoredHashForUser($pdo, $username);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error.']);
        exit;
    }

    if ($storedHash === null || !verifyStaffKey($inputKey, $storedHash)) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication failed.']);
        exit;
    }

    http_response_code(200);
    echo json_encode(['status' => 'authenticated']);
}
