<?php

declare(strict_types=1);

namespace App\Data\Dossiers;

/**
 * Staff-facing document request row on dossier show.
 */
final readonly class StaffDocumentRequestData
{
    public function __construct(
        public int $id,
        public string $type,
        public string $title,
        public ?string $instructions,
        public string $status,
        public ?string $answerText,
        public ?bool $answerBoolean,
        public ?string $answeredAt,
        public ?string $reviewedAt,
        public ?string $reviewedByName,
        public ?string $rejectionReason,
        public int $sortOrder,
        public ?StaffUploadedDocumentData $uploadedDocument,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     type: string,
     *     title: string,
     *     instructions: string|null,
     *     status: string,
     *     answer_text: string|null,
     *     answer_boolean: bool|null,
     *     answered_at: string|null,
     *     reviewed_at: string|null,
     *     reviewed_by_name: string|null,
     *     rejection_reason: string|null,
     *     sort_order: int,
     *     uploaded_document: array{
     *         id: int,
     *         original_filename: string,
     *         size_bytes: int,
     *         uploaded_at: string,
     *         processing_status: string,
     *         processing_error: string|null
     *     }|null
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'instructions' => $this->instructions,
            'status' => $this->status,
            'answer_text' => $this->answerText,
            'answer_boolean' => $this->answerBoolean,
            'answered_at' => $this->answeredAt,
            'reviewed_at' => $this->reviewedAt,
            'reviewed_by_name' => $this->reviewedByName,
            'rejection_reason' => $this->rejectionReason,
            'sort_order' => $this->sortOrder,
            'uploaded_document' => $this->uploadedDocument?->toArray(),
        ];
    }
}
