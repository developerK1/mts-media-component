<?php

declare(strict_types=1);

namespace MTS\Media\Validation;

use finfo;
use MTS\Media\Exception\MediaUploadException;
use MTS\Media\Upload\UploadFile;
use MTS\Media\Upload\ValidatedImage;

final class ImageUploadValidator
{
    /** @var array<string, string> */
    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(private readonly array $config)
    {
    }

    public function validate(UploadFile $file): ValidatedImage
    {
        if ($file->uploadError !== UPLOAD_ERR_OK) {
            throw new MediaUploadException($this->uploadErrorMessage($file->uploadError));
        }

        if (!is_file($file->temporaryPath) || !is_readable($file->temporaryPath)) {
            throw new MediaUploadException('The uploaded temporary file is missing or unreadable.');
        }

        if (!is_uploaded_file($file->temporaryPath)) {
            throw new MediaUploadException('The file was not received through a valid HTTP upload.');
        }

        $uploadConfig = $this->config['upload'] ?? [];
        $maxBytes = (int) ($uploadConfig['max_file_size'] ?? 10 * 1024 * 1024);
        $actualSize = filesize($file->temporaryPath);

        if ($actualSize === false || $actualSize < 1) {
            throw new MediaUploadException('The uploaded file is empty or could not be measured.');
        }

        if ($actualSize > $maxBytes) {
            throw new MediaUploadException('The uploaded file exceeds the maximum permitted size.');
        }

        // The server-side file size is authoritative, not $_FILES['size'].
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file->temporaryPath);

        if (!is_string($mimeType) || !isset(self::MIME_EXTENSIONS[$mimeType])) {
            throw new MediaUploadException('Unsupported image type. Allowed formats are JPG, PNG, and WebP.');
        }

        $allowed = $uploadConfig['allowed_mime_types'] ?? array_keys(self::MIME_EXTENSIONS);
        if (!in_array($mimeType, $allowed, true)) {
            throw new MediaUploadException('This image format is not enabled by the component configuration.');
        }

        $imageInfo = @getimagesize($file->temporaryPath);
        if ($imageInfo === false || !isset($imageInfo[0], $imageInfo[1], $imageInfo['mime'])) {
            throw new MediaUploadException('The uploaded file is not a valid, readable image.');
        }

        if ($imageInfo['mime'] !== $mimeType) {
            throw new MediaUploadException('The image content type is inconsistent.');
        }

        $width = (int) $imageInfo[0];
        $height = (int) $imageInfo[1];
        $maxWidth = (int) ($uploadConfig['max_width'] ?? 10000);
        $maxHeight = (int) ($uploadConfig['max_height'] ?? 10000);

        if ($width < 1 || $height < 1 || $width > $maxWidth || $height > $maxHeight) {
            throw new MediaUploadException('The image dimensions are outside the permitted range.');
        }

        $sha256 = hash_file('sha256', $file->temporaryPath);
        if (!is_string($sha256)) {
            throw new MediaUploadException('Could not calculate the image checksum.');
        }

        return new ValidatedImage(
            $file,
            $mimeType,
            self::MIME_EXTENSIONS[$mimeType],
            $width,
            $height,
            (int) $actualSize,
            $sha256,
            $this->sanitiseOriginalName($file->originalName),
        );
    }

    private function sanitiseOriginalName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[^A-Za-z0-9._ -]/u', '_', $name) ?? 'image';
        $name = trim($name, " .\t\n\r\0\x0B");

        if ($name === '') {
            return 'image';
        }

        return substr($name, 0, 255);
    }

    private function uploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the server upload limit.',
            UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was selected.',
            UPLOAD_ERR_NO_TMP_DIR => 'The server temporary upload directory is missing.',
            UPLOAD_ERR_CANT_WRITE => 'The server could not write the uploaded file.',
            UPLOAD_ERR_EXTENSION => 'A server extension stopped the upload.',
            default => 'The upload failed with an unknown error.',
        };
    }
}
