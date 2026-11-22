<?php

declare(strict_types=1);

/**
 * Minimal runtime bootstrap.
 *
 * The consuming application supplies a configured PDO connection and media
 * configuration. This file deliberately does not connect to a database,
 * create tables, or perform filesystem writes merely by being included.
 */

spl_autoload_register(static function (string $class): void {
    $prefix = 'MTS\\Media\\';
    $baseDirectory = __DIR__ . '/src/';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDirectory . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

/**
 * Load the component configuration.
 *
 * @return array<string, mixed>
 */
function mts_media_config(?string $configPath = null): array
{
    $path = $configPath ?? __DIR__ . '/config/media.php';

    if (!is_file($path)) {
        throw new RuntimeException(
            'Media configuration not found. Copy config/media.example.php to config/media.php and configure it.'
        );
    }

    $config = require $path;

    if (!is_array($config)) {
        throw new RuntimeException('Media configuration must return an array.');
    }

    return $config;
}
