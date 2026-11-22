<?php

declare(strict_types=1);

namespace MTS\Media\Upload;

final readonly class UploadResult implements \JsonSerializable
{
    /**
     * @param array<string, mixed>|null $media
     */
    private function __construct(
        public bool $success,
        public ?array $media,
        public ?string $error,
    ) {
    }

    /** @param array<string, mixed> $media */
    public static function success(array $media): self
    {
        return new self(true, $media, null);
    }

    public static function failure(string $error): self
    {
        return new self(false, null, $error);
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'success' => $this->success,
            'media' => $this->media,
            'error' => $this->error,
        ];
    }
}
