# Architecture and component boundaries

## Deployment model

Each application receives its own copy of this component, its own media tables in that application's configured database, and its own private media storage directory. There is no central MTS media database, shared media API, or cross-application file store.

## Responsibility boundaries

### Consuming application

- Authenticates the current user.
- Checks whether that user may upload, view, replace, or delete media for the relevant application record.
- Receives the HTTP request and passes uploaded file data to the component.
- Stores application-specific relationships, such as `product_media`.
- Decides when a media item is no longer needed under its own business rules.

### Media component

- Validates upload status, actual content, MIME type, size, dimensions, and decodability.
- Generates an opaque unique media ID and random storage keys.
- Re-encodes images using GD and creates configured variants.
- Stores files under a private storage root.
- Persists technical metadata and processing logs.
- Provides operations to retrieve metadata, replace a file, and delete media files.
- Delivers media without revealing physical storage paths.

## Upload lifecycle

1. Browser submits one or more files to the consuming application's endpoint.
2. Application authenticates the request and checks application-specific permissions and CSRF protections.
3. Application calls the component upload service with PHP upload data.
4. Component validates actual content; it does not trust filenames or browser MIME headers.
5. Component decodes and re-encodes the image, generates variants, and writes files using random storage keys.
6. Component records metadata and returns a structured result containing the media ID and safe delivery reference.
7. Application stores its own relationship to the media ID.
8. Browser requests an image through PHP delivery. The application checks access before the component streams the private file.

## Database ownership

The component owns only:
- `media_schema_migrations`
- `media_assets`
- `media_variants`
- `media_upload_logs`

The consuming application owns all relationship and business tables. For example, MyCatalog may create `product_media(product_id, media_id, display_order)`. That table is not part of this component.

The component receives a configured PDO connection. It must not create a central database or silently run migrations on page load.

## Storage rules

- Store originals and variants outside the public web root.
- Persist relative storage keys, never absolute host paths in database records.
- Generate storage keys server-side; never use client filenames as storage paths.
- Do not commit uploaded files, secrets, or production configuration to Git.
- A delivery URL is a locator, not permanent identity. The media ID is the stable identifier.

## Security and deletion

The component is not an authorisation system. The consuming application must check permissions before calling delivery, replacement, or deletion operations. The component performs the technical operation after that check.

Application-specific retention and orphan cleanup are out of scope for V1. Temporary files and partial outputs must still be cleaned up when an upload or processing operation fails.
