<?php

declare(strict_types=1);

/**
 * Copy to config/database.php and enter local database credentials.
 * config/database.php is ignored by Git and must never be committed.
 */
return [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => (int) (getenv('DB_PORT') ?: 3306),
    'database' => getenv('DB_DATABASE') ?: 'mts_media_demo',
    'username' => getenv('DB_USERNAME') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset' => 'utf8mb4',
];
