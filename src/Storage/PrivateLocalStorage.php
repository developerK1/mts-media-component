<?php

declare(strict_types=1);

namespace MTS\Media\Storage;

use MTS\Media\Exception\MediaUploadException;
use MTS\Media\Processing\GdImageProcessor;
use MTS\Media\Upload\ValidatedImage;

final class PrivateLocalStorage
{
    private string $root;

    /** @param array<string, mixed> $config */
    public function __construct(
        array $config,
        private readonly GdImageProcessor $processor = new GdImageProcessor()
    ) {
        $root = $config['storage']['root'] ?? null;
        if (!is_string($root) || trim($root) === '') {
            throw new MediaUploadException('A private media storage root must be configured.');
        }

        $this->root = rtrim($root, DIRECTORY_SEPARATOR);
        if ($this->root === '') {
            throw new MediaUploadException('The media storage root is invalid.');
        }
    }

    /**
     * Store a clean re-encoded original and its generated variants.
     *
     * @param array<string, mixed> $config
     * @return array{
     *   storage_key: string,
     *   file_size: int,
     *   width: int,
     *   height: int,
     *   checksum_sha256: string,
     *   variants: array<string, array{storage_key: string, mime_type: string, file_size: int, width: int, height: int}>
     * }
     */
    public function storeProcessed(string $mediaId, ValidatedImage $image, array $config): array
    {
        $prefix = substr($mediaId, 0, 2);
        $baseDirectory = $this->root . DIRECTORY_SEPARATOR . $prefix;
        $variantDirectory = $baseDirectory . DIRECTORY_SEPARATOR . $mediaId . '_variants';
        $this->ensureDirectory($baseDirectory);

        $extension = $image->extension;
        $originalKey = $prefix . '/' . $mediaId . '.' . $extension;
        $originalPath = $baseDirectory . DIRECTORY_SEPARATOR . $mediaId . '.' . $extension;

        if (file_exists($originalPath)) {
            throw new MediaUploadException('A storage collision occurred. Please retry the upload.');
        }

        try {
            $processingResult = $this->processor->process(
                $image->file->temporaryPath,
                $originalPath,
                $variantDirectory,
                $image->mimeType,
                $config
            );

            $variants = [];
            foreach ($processingResult['variants'] as $name => $details) {
                $key = $prefix . '/' . $mediaId . '_variants/' . $name . '.' . $extension;
                $variants[$name] = [
                    'storage_key' => $key,
                    'mime_type' => $image->mimeType,
                    'file_size' => $details['file_size'],
                    'width' => $details['width'],
                    'height' => $details['height'],
                ];
            }

            $checksum = hash_file('sha256', $originalPath);
            if (!is_string($checksum)) {
                throw new MediaUploadException('Could not calculate the processed image checksum.');
            }

            return [
                'storage_key' => $originalKey,
                'file_size' => $processingResult['file_size'],
                'width' => $processingResult['width'],
                'height' => $processingResult['height'],
                'checksum_sha256' => $checksum,
                'variants' => $variants,
            ];
        } catch (\Throwable $exception) {
            $this->removeDirectoryFiles($variantDirectory);
            if (is_file($originalPath)) {
                @unlink($originalPath);
            }
            if ($exception instanceof MediaUploadException) {
                throw $exception;
            }
            throw new MediaUploadException('The processed image could not be stored.');
        }
    }

    public function deleteByStorageKey(string $storageKey): void
    {
        $path = $this->resolveSafePath($storageKey);
        if (is_file($path) && !unlink($path)) {
            throw new MediaUploadException('Could not remove a stored media file.');
        }
    }

    private function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }
        if (!mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new MediaUploadException('Could not create a private media storage directory.');
        }
        @chmod($directory, 0750);
    }

    private function removeDirectoryFiles(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        foreach (glob($directory . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        @rmdir($directory);
    }

    private function resolveSafePath(string $storageKey): string
    {
        if (
            $storageKey === '' ||
            str_contains($storageKey, '\\') ||
            str_starts_with($storageKey, '/') ||
            preg_match('#(^|/)\.\.(/|$)#', $storageKey)
        ) {
            throw new MediaUploadException('Invalid media storage key.');
        }

        return $this->root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $storageKey);
    }
}
