<?php

declare(strict_types=1);

namespace MTS\Media\Processing;

final readonly class ProcessedImageSet
{
    /**
     * @param array<string, array{storage_key: string, mime_type: string, file_size: int, width: int, height: int}> $variants
     */
    public function __construct(
        public string $originalStorageKey,
        public string $originalMimeType,
        public int $originalFileSize,
        public int $originalWidth,
        public int $originalHeight,
        public array $variants,
    ) {
    }
}
