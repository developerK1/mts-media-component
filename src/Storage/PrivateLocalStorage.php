<?php

declare(strict_types=1);

namespace MTS\Media\Storage;

use MTS\Media\Exception\MediaUploadException;
use MTS\Media\Upload\ValidatedImage;

final class PrivateLocalStorage
{
    private string $root;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
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
     * Move a validated PHP upload into a generated private storage key.
     *
     * @return array{storage_key: string, absolute_path: string}
     */
    public function storeOriginal(string $mediaId, ValidatedImage $image): array
    {
        $directory = $this->root . DIRECTORY_SEPARATOR . substr($mediaId, 0, 2);
        $this->ensureDirectory($directory);

        $storageKey = substr($mediaId, 0, 2) . '/' . $mediaId . '.' . $image->extension;
        $destination = $this->root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $storageKey);

        if (file_exists($destination)) {
            throw new MediaUploadException('A storage collision occurred. Please retry the upload.');
        }

        if (!move_uploaded_file($image->file->temporaryPath, $destination)) {
            throw new MediaUploadException('The server could not move the uploaded image into private storage.');
        }

        @chmod($destination, 0640);

        return [
            'storage_key' => $storageKey,
            'absolute_path' => $destination,
        ];
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
            throw new MediaUploadException('Could not create the private media storage directory.');
        }

        @chmod($directory, 0750);
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
