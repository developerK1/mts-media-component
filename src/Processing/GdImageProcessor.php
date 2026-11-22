<?php

declare(strict_types=1);

namespace MTS\Media\Processing;

use MTS\Media\Exception\MediaUploadException;

final class GdImageProcessor
{
    /**
     * Decode, re-encode and create configured variants.
     *
     * The input file is never copied byte-for-byte into permanent storage.
     * Re-encoding removes metadata not represented by the decoded pixel data.
     *
     * @param array<string, mixed> $config
     * @return array{width: int, height: int, file_size: int, variants: array<string, array{file_size: int, width: int, height: int}>}
     */
    public function process(
        string $sourcePath,
        string $originalDestination,
        string $variantsDirectory,
        string $mimeType,
        array $config
    ): array {
        $this->assertGdSupport($mimeType);

        $imageInfo = @getimagesize($sourcePath);
        if ($imageInfo === false) {
            throw new MediaUploadException('The image could not be decoded for processing.');
        }

        $width = (int) $imageInfo[0];
        $height = (int) $imageInfo[1];
        $processing = $config['processing'] ?? [];
        $maxPixels = (int) ($config['upload']['max_pixels'] ?? 40000000);

        if ($width < 1 || $height < 1 || ($width * $height) > $maxPixels) {
            throw new MediaUploadException('The image pixel count exceeds the processing safety limit.');
        }

        $source = $this->decode($sourcePath, $mimeType);
        if ($source === false) {
            throw new MediaUploadException('GD could not decode the uploaded image.');
        }

        $createdFiles = [];
        try {
            $this->ensureDirectory(dirname($originalDestination));
            $this->writeImage(
                $source,
                $originalDestination,
                $mimeType,
                $processing
            );
            $createdFiles[] = $originalDestination;

            $variantConfig = $processing['variants'] ?? [];
            if (!is_array($variantConfig)) {
                throw new MediaUploadException('Image variant configuration must be an array.');
            }

            $this->ensureDirectory($variantsDirectory);
            $variantResults = [];

            foreach ($variantConfig as $name => $settings) {
                if (
                    !is_string($name) ||
                    !preg_match('/^[a-zA-Z0-9_-]{1,50}$/', $name) ||
                    !is_array($settings)
                ) {
                    throw new MediaUploadException('An image variant has invalid configuration.');
                }

                $targetWidth = (int) ($settings['width'] ?? 0);
                $targetHeight = (int) ($settings['height'] ?? 0);
                if ($targetWidth < 1 || $targetHeight < 1 || $targetWidth > 10000 || $targetHeight > 10000) {
                    throw new MediaUploadException("Image variant '{$name}' has invalid dimensions.");
                }

                [$variantImage, $variantWidth, $variantHeight] = $this->resizeContain(
                    $source,
                    $width,
                    $height,
                    $targetWidth,
                    $targetHeight,
                    $mimeType
                );

                $destination = $variantsDirectory . DIRECTORY_SEPARATOR . $name . '.' . $this->extensionFor($mimeType);
                try {
                    $this->writeImage($variantImage, $destination, $mimeType, $processing);
                } finally {
                    imagedestroy($variantImage);
                }

                $createdFiles[] = $destination;
                $size = filesize($destination);
                if ($size === false) {
                    throw new MediaUploadException("Could not determine the size of image variant '{$name}'.");
                }

                $variantResults[$name] = [
                    'file_size' => (int) $size,
                    'width' => $variantWidth,
                    'height' => $variantHeight,
                ];
            }

            $originalSize = filesize($originalDestination);
            if ($originalSize === false) {
                throw new MediaUploadException('Could not determine the processed original image size.');
            }

            return [
                'width' => $width,
                'height' => $height,
                'file_size' => (int) $originalSize,
                'variants' => $variantResults,
            ];
        } catch (\Throwable $exception) {
            foreach ($createdFiles as $createdFile) {
                if (is_file($createdFile)) {
                    @unlink($createdFile);
                }
            }

            if ($exception instanceof MediaUploadException) {
                throw $exception;
            }

            throw new MediaUploadException('The image could not be processed.');
        } finally {
            imagedestroy($source);
        }
    }

    private function assertGdSupport(string $mimeType): void
    {
        if (!extension_loaded('gd')) {
            throw new MediaUploadException('The PHP GD extension is required for image processing.');
        }

        $available = match ($mimeType) {
            'image/jpeg' => function_exists('imagecreatefromjpeg') && function_exists('imagejpeg'),
            'image/png' => function_exists('imagecreatefrompng') && function_exists('imagepng'),
            'image/webp' => function_exists('imagecreatefromwebp') && function_exists('imagewebp'),
            default => false,
        };

        if (!$available) {
            throw new MediaUploadException('The server GD installation does not support this image format.');
        }
    }

    private function decode(string $path, string $mimeType): \GdImage|false
    {
        return match ($mimeType) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => false,
        };
    }

    /**
     * @param array<string, mixed> $processing
     */
    private function writeImage(\GdImage $image, string $destination, string $mimeType, array $processing): void
    {
        $success = match ($mimeType) {
            'image/jpeg' => @imagejpeg($image, $destination, max(0, min(100, (int) ($processing['jpeg_quality'] ?? 85)))),
            'image/png' => @imagepng($image, $destination, max(0, min(9, (int) ($processing['png_compression'] ?? 6)))),
            'image/webp' => @imagewebp($image, $destination, max(0, min(100, (int) ($processing['webp_quality'] ?? 82)))),
            default => false,
        };

        if (!$success || !is_file($destination) || filesize($destination) === 0) {
            throw new MediaUploadException('GD could not write a processed image file.');
        }

        @chmod($destination, 0640);
    }

    /**
     * Resize within the requested box without cropping or upscaling.
     *
     * @return array{\GdImage, int, int}
     */
    private function resizeContain(
        \GdImage $source,
        int $sourceWidth,
        int $sourceHeight,
        int $boxWidth,
        int $boxHeight,
        string $mimeType
    ): array {
        $scale = min(1, $boxWidth / $sourceWidth, $boxHeight / $sourceHeight);
        $newWidth = max(1, (int) round($sourceWidth * $scale));
        $newHeight = max(1, (int) round($sourceHeight * $scale));

        $target = imagecreatetruecolor($newWidth, $newHeight);
        if (!$target instanceof \GdImage) {
            throw new MediaUploadException('GD could not allocate an image variant.');
        }

        if ($mimeType !== 'image/jpeg') {
            imagealphablending($target, false);
            imagesavealpha($target, true);
            $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
            imagefilledrectangle($target, 0, 0, $newWidth, $newHeight, $transparent);
        } else {
            $white = imagecolorallocate($target, 255, 255, 255);
            imagefilledrectangle($target, 0, 0, $newWidth, $newHeight, $white);
        }

        if (!imagecopyresampled(
            $target,
            $source,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $sourceWidth,
            $sourceHeight
        )) {
            imagedestroy($target);
            throw new MediaUploadException('GD could not resize the image variant.');
        }

        return [$target, $newWidth, $newHeight];
    }

    private function extensionFor(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw new MediaUploadException('Unsupported output image format.'),
        };
    }

    private function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (!mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new MediaUploadException('Could not create an image processing directory.');
        }

        @chmod($directory, 0750);
    }
}
