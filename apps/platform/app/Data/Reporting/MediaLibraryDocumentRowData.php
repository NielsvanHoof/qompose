<?php

declare(strict_types=1);

namespace App\Data\Reporting;

/**
 * Media library index row.
 */
final readonly class MediaLibraryDocumentRowData
{
    public function __construct(
        public int $id,
        public string $title,
        public string $status,
        public ?string $updatedAt,
        public MediaLibraryDossierSummaryData $dossier,
        public string $clientName,
        public ?MediaLibraryUploadedDocumentData $uploadedDocument,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     title: string,
     *     status: string,
     *     updated_at: string|null,
     *     dossier: array{id: int, title: string, reference: string|null},
     *     client_name: string,
     *     uploaded_document: array{
     *         id: int,
     *         original_filename: string,
     *         size_bytes: int,
     *         uploaded_at: string
     *     }|null
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status,
            'updated_at' => $this->updatedAt,
            'dossier' => $this->dossier->toArray(),
            'client_name' => $this->clientName,
            'uploaded_document' => $this->uploadedDocument?->toArray(),
        ];
    }
}
