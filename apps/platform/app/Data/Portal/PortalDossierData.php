<?php

declare(strict_types=1);

namespace App\Data\Portal;

/**
 * Dossier section of the client portal page.
 */
final readonly class PortalDossierData
{
    /**
     * @param  list<PortalDocumentRequestData>  $documentRequests
     */
    public function __construct(
        public string $title,
        public ?string $reference,
        public string $status,
        public ?string $dueDate,
        public string $clientName,
        public string $expiresAt,
        public PortalProgressData $progress,
        public array $documentRequests,
    ) {}

    /**
     * @return array{
     *     title: string,
     *     reference: string|null,
     *     status: string,
     *     due_date: string|null,
     *     client: array{name: string},
     *     expires_at: string,
     *     progress: array{
     *         total: int,
     *         completed: int,
     *         approved: int,
     *         remaining: int,
     *         next_incomplete: array{id: int, title: string}|null
     *     },
     *     document_requests: list<array{
     *         id: int,
     *         type: string,
     *         title: string,
     *         instructions: string|null,
     *         status: string,
     *         answer_text: string|null,
     *         answer_boolean: bool|null,
     *         rejection_reason: string|null,
     *         can_respond: bool,
     *         uploaded_document: array{
     *             original_filename: string,
     *             size_bytes: int,
     *             uploaded_at: string
     *         }|null
     *     }>
     * }
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'reference' => $this->reference,
            'status' => $this->status,
            'due_date' => $this->dueDate,
            'client' => ['name' => $this->clientName],
            'expires_at' => $this->expiresAt,
            'progress' => $this->progress->toArray(),
            'document_requests' => array_map(
                static fn (PortalDocumentRequestData $request): array => $request->toArray(),
                $this->documentRequests,
            ),
        ];
    }
}
