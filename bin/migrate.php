<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Not found\n");
}

$root = dirname(__DIR__);
$configPath = $root . '/config/database.php';

if (!is_file($configPath)) {
    fwrite(STDERR, "Missing config/database.php. Create it from config/database.example.php.\n");
    exit(1);
}

$db = require $configPath;

if (!is_array($db)) {
    fwrite(STDERR, "Database configuration must return an array.\n");
    exit(1);
}

$required = ['host', 'database', 'username', 'password'];
foreach ($required as $key) {
    if (!array_key_exists($key, $db)) {
        fwrite(STDERR, "Missing database configuration key: {$key}\n");
        exit(1);
    }
}

$port = (int) ($db['port'] ?? 3306);
$charset = (string) ($db['charset'] ?? 'utf8mb4');

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $db['host'],
    $port,
    $db['database'],
    $charset
);

try {
    $pdo = new PDO($dsn, (string) $db['username'], (string) $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $migrationFiles = glob($root . '/database/migrations/*.sql') ?: [];
    sort($migrationFiles, SORT_STRING);

    if ($migrationFiles === []) {
        fwrite(STDOUT, "No migrations found.\n");
        exit(0);
    }

    foreach ($migrationFiles as $file) {
        $migration = basename($file);
        $sql = file_get_contents($file);

        if ($sql === false || trim($sql) === '') {
            throw new RuntimeException("Migration is empty or unreadable: {$migration}");
        }

        // The first migration creates the migration ledger itself.
        $ledgerExists = $pdo->query(
            "SHOW TABLES LIKE 'media_schema_migrations'"
        )->fetchColumn() !== false;

        if ($ledgerExists) {
            $check = $pdo->prepare(
                'SELECT 1 FROM media_schema_migrations WHERE migration = :migration LIMIT 1'
            );
            $check->execute(['migration' => $migration]);

            if ($check->fetchColumn()) {
                fwrite(STDOUT, "Already applied: {$migration}\n");
                continue;
            }
        }

        fwrite(STDOUT, "Applying: {$migration}\n");
        $pdo->exec($sql);

        // Migration 001 records itself in its SQL. Future migrations are
        // recorded by the runner after successful execution.
        $record = $pdo->prepare(
            'INSERT IGNORE INTO media_schema_migrations (migration) VALUES (:migration)'
        );
        $record->execute(['migration' => $migration]);

        fwrite(STDOUT, "Applied: {$migration}\n");
    }

    fwrite(STDOUT, "Migration run completed.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, "Migration failed: " . $exception->getMessage() . "\n");
    exit(1);
}
