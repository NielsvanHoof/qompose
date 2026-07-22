<?php

declare(strict_types=1);

namespace App\Data\Portal;

/**
 * Uploaded file shown to the client on the portal.
 */
final readonly class PortalUploadedDocumentData
{
    public function __construct(
        public string $originalFilename,
        public int $sizeBytes,
        public string $uploadedAt,
    ) {}

    /**
     * @return array{
     *     original_filename: string,
     *     size_bytes: int,
     *     uploaded_at: string
     * }
     */
    public function toArray(): array
    {
        return [
            'original_filename' => $this->originalFilename,
            'size_bytes' => $this->sizeBytes,
            'uploaded_at' => $this->uploadedAt,
        ];
    }
}
