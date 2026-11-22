<?php

declare(strict_types=1);

namespace MTS\Media\Upload;

use MTS\Media\Exception\MediaUploadException;
use MTS\Media\Processing\GdImageProcessor;
use MTS\Media\Storage\PrivateLocalStorage;
use MTS\Media\Validation\ImageUploadValidator;

final class MediaUploadService
{
    private ImageUploadValidator $validator;
    private PrivateLocalStorage $storage;
    private int $maxFiles;

    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config)
    {
        $this->validator = new ImageUploadValidator($config);
        $this->storage = new PrivateLocalStorage($config, new GdImageProcessor());
        $this->maxFiles = max(1, (int) ($config['upload']['max_files_per_request'] ?? 10));
    }

    /** @param array<string, mixed> $phpFile */
    public function upload(array $phpFile): UploadResult
    {
        try {
            $input = UploadFile::fromPhpArray($phpFile);
            $validated = $this->validator->validate($input);
            $mediaId = $this->generateUuidV4();
            $stored = $this->storage->storeProcessed($mediaId, $validated, $this->config);

            return UploadResult::success([
                'media_id' => $mediaId,
                'original_name' => $validated->safeOriginalName,
                'mime_type' => $validated->mimeType,
                'extension' => $validated->extension,
                'file_size' => $stored['file_size'],
                'width' => $stored['width'],
                'height' => $stored['height'],
                'checksum_sha256' => $validated->sha256,
                'storage_key' => $stored['storage_key'],
                'variants' => $stored['variants'],
            ]);
        } catch (MediaUploadException $exception) {
            return UploadResult::failure($exception->getMessage());
        } catch (\Throwable) {
            return UploadResult::failure('The image could not be uploaded due to an internal processing error.');
        }
    }

    /** @param array<string, mixed> $phpFiles @return list<UploadResult> */
    public function uploadMany(array $phpFiles): array
    {
        try {
            $files = UploadFile::fromMultiplePhpArray($phpFiles);
        } catch (MediaUploadException $exception) {
            return [UploadResult::failure($exception->getMessage())];
        }

        if (count($files) > $this->maxFiles) {
            return [UploadResult::failure('Too many files were submitted in one request.')];
        }

        $results = [];
        foreach ($files as $file) {
            $results[] = $this->upload([
                'tmp_name' => $file->temporaryPath,
                'name' => $file->originalName,
                'size' => $file->reportedSize,
                'error' => $file->uploadError,
            ]);
        }

        return $results;
    }

    private function generateUuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }
}
