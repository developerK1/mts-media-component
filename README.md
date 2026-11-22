# MTS Media Component

A reusable, portable PHP 8.4 component for secure image uploads, processing, private storage, and controlled delivery.

This is a **local component installed separately inside each PHP application**. It is not a central media platform or remote API. Each consuming application uses its own database and private storage directory.

## V1 scope

- JPG, PNG, and WebP uploads
- Single and multiple uploads
- Content-based validation, size and dimension limits, and image decode checks
- Safe image re-encoding and GD-based resizing
- Original image plus generated variants
- Unique media identifiers and metadata persistence
- Private storage outside the public web root
- PHP delivery controller that serves media by ID without exposing physical paths
- Explicit replacement and deletion operations
- Reusable JavaScript upload helper and standalone demonstration dashboard
- Installation, migration, and testing documentation

## Architecture

1. **Consuming application** — authenticates the user, checks business permissions, receives the request, and owns business relationships (for example, product-to-media links).
2. **MTS Media Component** — validates, processes, stores, retrieves, replaces, and deletes media files and technical metadata.
3. **Private storage and delivery** — stores files outside the public web root and serves authorised requests through a PHP controller.

The component must not know about product IDs, business IDs, catalogue rules, subscriptions, or application-specific ownership. The consuming application performs those checks before calling the component.

## Requirements

- PHP 8.4
- MySQL 8+ or compatible MySQL/MariaDB
- PDO with MySQL driver
- GD with JPEG, PNG, and WebP support
- Writable private storage outside the public web root

No third-party runtime library is required for the initial implementation.

## Repository structure

```text
src/                    Component source code
config/                 Example configuration
database/migrations/    Versioned SQL migrations
public/                 Standalone demo entry points
resources/              Demo assets and JavaScript helper
tests/                  Automated tests
docs/                   Architecture and installation guides
storage/                Local-only placeholder; never commit uploaded media
```

## Development status

Foundation 1 is being established. Upload handling, image processing, delivery, demo UI, and automated tests follow in later phases.

See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) and [docs/DEVELOPMENT_PLAN.md](docs/DEVELOPMENT_PLAN.md).

## Security baseline

- Never trust client filenames, extensions, or browser-supplied MIME types.
- Never place uploaded originals or variants in a publicly browsable directory.
- Generate internal storage names independently of user filenames.
- The consuming application must authorise access before delivery or deletion.
- Do not expose absolute storage paths in API responses or browser markup.
- Keep credentials and environment-specific configuration out of Git.
