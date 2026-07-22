<?php

declare(strict_types=1);

namespace App\Data\Dossiers;

/**
 * Uploaded file metadata for a staff document-request row.
 */
final readonly class StaffUploadedDocumentData
{
    public function __construct(
        public int $id,
        public string $originalFilename,
        public int $sizeBytes,
        public string $uploadedAt,
        public string $processingStatus,
        public ?string $processingError,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     original_filename: string,
     *     size_bytes: int,
     *     uploaded_at: string,
     *     processing_status: string,
     *     processing_error: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'original_filename' => $this->originalFilename,
            'size_bytes' => $this->sizeBytes,
            'uploaded_at' => $this->uploadedAt,
            'processing_status' => $this->processingStatus,
            'processing_error' => $this->processingError,
        ];
    }
}
