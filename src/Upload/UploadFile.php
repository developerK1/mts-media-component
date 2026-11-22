<?php

declare(strict_types=1);

namespace MTS\Media\Upload;

use MTS\Media\Exception\MediaUploadException;

final readonly class UploadFile
{
    public function __construct(
        public string $temporaryPath,
        public string $originalName,
        public int $reportedSize,
        public int $uploadError,
    ) {
    }

    /**
     * Build a single-file input from one PHP $_FILES entry.
     *
     * @param array<string, mixed> $file
     */
    public static function fromPhpArray(array $file): self
    {
        foreach (['tmp_name', 'name', 'size', 'error'] as $key) {
            if (!array_key_exists($key, $file)) {
                throw new MediaUploadException("Malformed upload input: missing {$key}.");
            }
        }

        if (!is_string($file['tmp_name']) || !is_string($file['name'])) {
            throw new MediaUploadException('Malformed upload input: invalid filename data.');
        }

        if (!is_numeric($file['size']) || !is_numeric($file['error'])) {
            throw new MediaUploadException('Malformed upload input: invalid size or upload status.');
        }

        return new self(
            $file['tmp_name'],
            $file['name'],
            (int) $file['size'],
            (int) $file['error'],
        );
    }

    /**
     * Convert a PHP multiple-file input into individual file entries.
     *
     * @param array<string, mixed> $files
     * @return list<self>
     */
    public static function fromMultiplePhpArray(array $files): array
    {
        foreach (['name', 'tmp_name', 'size', 'error'] as $key) {
            if (!isset($files[$key]) || !is_array($files[$key])) {
                throw new MediaUploadException('Malformed multiple-upload input.');
            }
        }

        $count = count($files['name']);
        if (
            count($files['tmp_name']) !== $count ||
            count($files['size']) !== $count ||
            count($files['error']) !== $count
        ) {
            throw new MediaUploadException('Multiple-upload arrays have inconsistent lengths.');
        }

        $items = [];
        for ($index = 0; $index < $count; $index++) {
            $items[] = self::fromPhpArray([
                'name' => $files['name'][$index],
                'tmp_name' => $files['tmp_name'][$index],
                'size' => $files['size'][$index],
                'error' => $files['error'][$index],
            ]);
        }

        return $items;
    }
}
