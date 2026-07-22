<?php

declare(strict_types=1);

namespace App\Data\Reporting;

/**
 * Uploaded document nested in a media library row.
 */
final readonly class MediaLibraryUploadedDocumentData
{
    public function __construct(
        public int $id,
        public string $originalFilename,
        public int $sizeBytes,
        public string $uploadedAt,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     original_filename: string,
     *     size_bytes: int,
     *     uploaded_at: string
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'original_filename' => $this->originalFilename,
            'size_bytes' => $this->sizeBytes,
            'uploaded_at' => $this->uploadedAt,
        ];
    }
}
