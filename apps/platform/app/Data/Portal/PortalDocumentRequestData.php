<?php

declare(strict_types=1);

namespace App\Data\Portal;

/**
 * Client-facing document request row on the portal.
 */
final readonly class PortalDocumentRequestData
{
    public function __construct(
        public int $id,
        public string $type,
        public string $title,
        public ?string $instructions,
        public string $status,
        public ?string $answerText,
        public ?bool $answerBoolean,
        public ?string $rejectionReason,
        public bool $canRespond,
        public ?PortalUploadedDocumentData $uploadedDocument,
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
     *     rejection_reason: string|null,
     *     can_respond: bool,
     *     uploaded_document: array{
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
            'type' => $this->type,
            'title' => $this->title,
            'instructions' => $this->instructions,
            'status' => $this->status,
            'answer_text' => $this->answerText,
            'answer_boolean' => $this->answerBoolean,
            'rejection_reason' => $this->rejectionReason,
            'can_respond' => $this->canRespond,
            'uploaded_document' => $this->uploadedDocument?->toArray(),
        ];
    }
}
