# Development plan

## Phase 1 — Foundation

- [x] Establish repository and project documentation
- [x] Define component boundaries and deployment model
- [x] Add example configuration and ignore rules
- [x] Add initial database migration
- [ ] Add PHP bootstrap and component autoloading
- [ ] Add migration runner and environment validation
- [ ] Verify PHP 8.4, PDO MySQL, and GD requirements

## Phase 2 — Upload engine

- [ ] Accept single and multiple PHP uploads
- [ ] Validate upload errors, file size, MIME/content, dimensions, and image decoding
- [ ] Generate UUID media IDs and random storage keys
- [ ] Store validated originals in private storage
- [ ] Return consistent success and failure results

## Phase 3 — Image processing

- [ ] Decode and re-encode JPEG, PNG, and WebP using GD
- [ ] Generate thumbnail, medium, and large variants
- [ ] Preserve transparency where supported
- [ ] Apply configurable quality and dimension limits
- [ ] Clean up partial files on failure

## Phase 4 — Metadata and lifecycle

- [ ] Persist asset and variant metadata using PDO prepared statements
- [ ] Record technical upload and processing events
- [ ] Implement metadata retrieval
- [ ] Implement explicit replacement and deletion
- [ ] Add transaction and filesystem failure handling

## Phase 5 — Controlled delivery

- [ ] Resolve media by stable media ID
- [ ] Serve private files through PHP with safe content headers
- [ ] Support variant selection
- [ ] Ensure physical paths are never returned to the browser
- [ ] Document the application authorisation hook

## Phase 6 — Standalone demonstration

- [ ] Build upload dashboard and gallery
- [ ] Add multiple-file selection, previews, and progress
- [ ] Add JavaScript upload helper
- [ ] Add media details and explicit delete action
- [ ] Demonstrate application-owned relationship handling separately

## Phase 7 — Testing and MyCatalog integration

- [ ] Test valid JPG, PNG, and WebP files
- [ ] Test unsupported, oversized, corrupt, and malformed uploads
- [ ] Test multiple uploads and partial failures
- [ ] Test storage and database failures
- [ ] Test delivery and access-control integration
- [ ] Test replacement and deletion
- [ ] Install a local copy into MyCatalog and verify the sneaker shop workflow

## V1 definition of done

A clean PHP application can install the component, run migrations explicitly, configure private storage, upload one or many supported images, receive stable media IDs, display generated variants through controlled PHP delivery, and delete media after its own permission checks. The component remains independent of application business entities.
