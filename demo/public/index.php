<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use SybaseConnector\DBAL\Driver\SybaseDriver;
use SybaseConnector\DBAL\SchemaManager\SQLAnywhereSchemaManagerFactory;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;

// Load .env
if (file_exists(__DIR__ . '/../../.env')) {
    foreach (file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line !== '' && !str_starts_with($line, '#')) {
            putenv($line);
        }
    }
}

$dsn = getenv('SYBASE_DSN') ?: '';
$user = getenv('SYBASE_USER') ?: '';
$password = getenv('SYBASE_PASSWORD') ?: '';

$sql = trim($_GET['q'] ?? '');
$rows = [];
$error = null;
$duration = null;

if ($sql !== '') {
    try {
        $connection = DriverManager::getConnection([
            'driverClass' => SybaseDriver::class,
            'driverOptions' => ['dsn' => $dsn],
            'user' => $user,
            'password' => $password,
            'schema_manager_factory' => new SQLAnywhereSchemaManagerFactory(),
        ], new Configuration());

        $start = microtime(true);
        $result = $connection->executeQuery($sql);
        $rows = $result->fetchAllAssociative();
        $duration = round((microtime(true) - $start) * 1000);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL Anywhere — Query Demo</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f5f5f5; color: #333; padding: 2rem; }
        h1 { font-size: 1.5rem; margin-bottom: 1rem; }
        .search-box { display: flex; gap: .5rem; margin-bottom: 1.5rem; max-width: 600px; }
        .search-box input { flex: 1; padding: .6rem .8rem; border: 1px solid #ccc; border-radius: 6px; font-size: 1rem; font-family: monospace; }
        .search-box button { padding: .6rem 1.2rem; background: #2563eb; color: #fff; border: none; border-radius: 6px; font-size: 1rem; cursor: pointer; }
        .search-box button:hover { background: #1d4ed8; }
        .meta { color: #666; font-size: .85rem; margin-bottom: 1rem; }
        .error { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; padding: .8rem; border-radius: 6px; margin-bottom: 1rem; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        th { background: #f8fafc; text-align: left; padding: .7rem .8rem; font-weight: 600; font-size: .85rem; color: #64748b; border-bottom: 2px solid #e2e8f0; }
        td { padding: .6rem .8rem; border-bottom: 1px solid #f1f5f9; font-size: .9rem; }
        tr:hover td { background: #f8fafc; }
        .empty { text-align: center; padding: 2rem; color: #94a3b8; }
    </style>
</head>
<body>
    <h1>SQL Anywhere — Query Demo</h1>

    <form class="search-box" method="get">
        <input type="text" name="q" value="<?= htmlspecialchars($sql) ?>" placeholder="SELECT TOP 10 * FROM your_table" autofocus>
        <button type="submit">Run</button>
    </form>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($sql !== '' && $error === null): ?>
        <p class="meta">
            <?= count($rows) ?> row(s)
            <?php if ($duration !== null): ?> — <?= $duration ?> ms<?php endif; ?>
        </p>

        <?php if ($rows === []): ?>
            <div class="empty">No results.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <?php foreach (array_keys($rows[0]) as $col): ?>
                            <th><?= htmlspecialchars($col) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <?php foreach ($row as $val): ?>
                                <td><?= htmlspecialchars((string) ($val ?? '')) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>
