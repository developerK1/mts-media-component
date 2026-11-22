# Installation (development foundation)

## Requirements

- PHP 8.4 CLI and web runtime
- PDO MySQL extension
- GD extension with JPEG, PNG, and WebP support
- MySQL-compatible database
- A private writable media directory outside the public web root

## Initial setup

1. Clone this repository into your development workspace.
2. Copy `config/media.example.php` to `config/media.php`.
3. Copy `config/database.example.php` to `config/database.php`.
4. Set database connection values in `config/database.php`.
5. Set `MEDIA_STORAGE_PATH` to a private directory outside the public web root.
6. Create the target database using your normal database administration process.
7. Run the migration explicitly from the project root:

   ```bash
   php bin/migrate.php
   ```

The migration runner is a CLI-only installation tool. Do not expose it through a public web route. Do not run migrations on ordinary page requests.

## Application integration status

The bootstrap and migration runner establish the project foundation only. The upload service, validation pipeline, GD processing, private storage implementation, and delivery controller are still planned work and are not yet available.

When integrating into an application, that application must authenticate the user and check business-level permissions before calling media operations.
