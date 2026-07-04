<?php
declare(strict_types=1);

require_once __DIR__ . '/db_config.php';

header('Content-Type: text/html; charset=UTF-8');

// Capture raw input and a pre-escaped copy for every output context.
$keyword     = isset($_GET['keyword']) ? (string) $_GET['keyword'] : '';
$safeKeyword = htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8');

try {
    $pdo  = getPDO();

    // Fix: parameterized LIKE query — user input is never concatenated into SQL.
    $stmt = $pdo->prepare(
        'SELECT id, name, illness_history
           FROM patient_records
          WHERE name LIKE :kw'
    );
    $stmt->bindValue(':kw', '%' . $keyword . '%', PDO::PARAM_STR);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    // Do not leak exception details; safe static string is fine here.
    echo htmlspecialchars('A database error occurred.', ENT_QUOTES, 'UTF-8');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Search</title>
</head>
<body>
<h1>Patient Search</h1>
<!-- Fix: keyword echoed through htmlspecialchars — no raw reflection into HTML -->
<p>Results for: <strong><?= $safeKeyword ?></strong></p>

<?php if (empty($rows)): ?>
    <!-- Fix: safe keyword used in the "no results" branch too -->
    <p>No records found for <em><?= $safeKeyword ?></em>.</p>
<?php else: ?>
    <table border="1" cellpadding="4">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Illness History</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <!-- Fix: every DB field escaped — stored XSS cannot execute -->
                <td><?= htmlspecialchars((string) $row['id'],              ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) $row['name'],            ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) $row['illness_history'], ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</body>
</html>
