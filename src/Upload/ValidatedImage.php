<?php

declare(strict_types=1);

namespace MTS\Media\Upload;

final readonly class ValidatedImage
{
    public function __construct(
        public UploadFile $file,
        public string $mimeType,
        public string $extension,
        public int $width,
        public int $height,
        public int $fileSize,
        public string $sha256,
        public string $safeOriginalName,
    ) {
    }
}
