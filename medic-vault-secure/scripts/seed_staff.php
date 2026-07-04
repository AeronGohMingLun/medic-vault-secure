<?php
declare(strict_types=1);

// Minimal .env loader — reads NAME=VALUE lines, skips comments and blanks.
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        putenv(trim($name) . '=' . trim($value));
    }
}

require_once __DIR__ . '/../vendor/autoload.php';

// Argon2id parameters: memory_cost=64 MiB, time_cost=4 iterations, threads=2.
$options = [
    'memory_cost' => 65536,
    'time_cost'   => 4,
    'threads'     => 2,
];

$users = [
    ['username' => 'dr_faizal',   'key' => 'FaizalSecureKey!2024'],
    ['username' => 'dr_sharifah', 'key' => 'SharafahSecureKey!2024'],
];

try {
    $pdo  = getPDO();
    $stmt = $pdo->prepare(
        'INSERT INTO staff_credentials (username, auth_key_hash)
              VALUES (:username, :hash)
         ON DUPLICATE KEY UPDATE auth_key_hash = VALUES(auth_key_hash)'
    );

    foreach ($users as $user) {
        $hash = password_hash($user['key'], PASSWORD_ARGON2ID, $options);
        $stmt->bindValue(':username', $user['username'], PDO::PARAM_STR);
        $stmt->bindValue(':hash',     $hash,             PDO::PARAM_STR);
        $stmt->execute();
        echo "Seeded: {$user['username']}\n";
    }
    echo "Done.\n";
} catch (PDOException $e) {
    fwrite(STDERR, 'DB error: ' . $e->getMessage() . "\n");
    exit(1);
}
