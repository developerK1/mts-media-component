-- MTS Media Component migration 001.
-- Apply explicitly during installation, never during ordinary page requests.
-- Business relationship tables belong to the consuming application.

CREATE TABLE IF NOT EXISTS media_schema_migrations (
    migration VARCHAR(190) NOT NULL,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (migration)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media_assets (
    media_id CHAR(36) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    original_name VARCHAR(255) NOT NULL,
    storage_key VARCHAR(500) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_extension VARCHAR(10) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    width INT UNSIGNED NOT NULL,
    height INT UNSIGNED NOT NULL,
    checksum_sha256 CHAR(64) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (media_id),
    UNIQUE KEY uq_media_assets_storage_key (storage_key),
    KEY idx_media_assets_status_created (status, created_at),
    KEY idx_media_assets_checksum (checksum_sha256)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media_variants (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    media_id CHAR(36) NOT NULL,
    variant_name VARCHAR(50) NOT NULL,
    storage_key VARCHAR(500) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    width INT UNSIGNED NOT NULL,
    height INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_media_variant_name (media_id, variant_name),
    UNIQUE KEY uq_media_variant_storage_key (storage_key),
    CONSTRAINT fk_media_variants_asset FOREIGN KEY (media_id)
        REFERENCES media_assets (media_id) ON DELETE CASCADE,
    KEY idx_media_variants_media_id (media_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media_upload_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    media_id CHAR(36) NULL,
    event_type VARCHAR(50) NOT NULL,
    message VARCHAR(500) NOT NULL,
    context_json JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_media_upload_logs_media_created (media_id, created_at),
    KEY idx_media_upload_logs_event_created (event_type, created_at),
    CONSTRAINT fk_media_upload_logs_asset FOREIGN KEY (media_id)
        REFERENCES media_assets (media_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO media_schema_migrations (migration)
VALUES ('001_create_media_tables');
