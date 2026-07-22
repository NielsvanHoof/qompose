<?php

declare(strict_types=1);

namespace App\Data\Dossiers;

use App\Data\Shared\PersonOptionData;

/**
 * Dossier detail object on the show page (without the templates list).
 */
final readonly class DossierShowData
{
    /**
     * @param  list<StaffDocumentRequestData>  $documentRequests
     * @param  list<DossierAccessGrantData>  $accessGrants
     */
    public function __construct(
        public int $id,
        public string $title,
        public ?string $reference,
        public string $status,
        public ?string $dueDate,
        public ?PersonOptionData $responsibleStaff,
        public ?int $reminderIntervalDays,
        public ?string $nextReminderAt,
        public ?string $lastClientMessageSentAt,
        public ?string $lastClientOpenedAt,
        public bool $hasOutstandingClientItems,
        public bool $readyToComplete,
        public DossierReviewSummaryData $reviewSummary,
        public DossierClientSummaryData $client,
        public array $documentRequests,
        public array $accessGrants,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     title: string,
     *     reference: string|null,
     *     status: string,
     *     due_date: string|null,
     *     responsible_staff: array{id: int, name: string, email: string}|null,
     *     reminder_interval_days: int|null,
     *     next_reminder_at: string|null,
     *     last_client_message_sent_at: string|null,
     *     last_client_opened_at: string|null,
     *     has_outstanding_client_items: bool,
     *     ready_to_complete: bool,
     *     review_summary: array{
     *         total: int,
     *         pending: int,
     *         submitted: int,
     *         accepted: int,
     *         rejected: int
     *     },
     *     client: array{name: string, email: string},
     *     document_requests: list<array{
     *         id: int,
     *         type: string,
     *         title: string,
     *         instructions: string|null,
     *         status: string,
     *         answer_text: string|null,
     *         answer_boolean: bool|null,
     *         answered_at: string|null,
     *         reviewed_at: string|null,
     *         reviewed_by_name: string|null,
     *         rejection_reason: string|null,
     *         sort_order: int,
     *         uploaded_document: array{
     *             id: int,
     *             original_filename: string,
     *             size_bytes: int,
     *             uploaded_at: string,
     *             processing_status: string,
     *             processing_error: string|null
     *         }|null
     *     }>,
     *     access_grants: list<array{
     *         id: int,
     *         expires_at: string,
     *         revoked_at: string|null,
     *         last_used_at: string|null,
     *         is_valid: bool
     *     }>
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'reference' => $this->reference,
            'status' => $this->status,
            'due_date' => $this->dueDate,
            'responsible_staff' => $this->responsibleStaff?->toArray(),
            'reminder_interval_days' => $this->reminderIntervalDays,
            'next_reminder_at' => $this->nextReminderAt,
            'last_client_message_sent_at' => $this->lastClientMessageSentAt,
            'last_client_opened_at' => $this->lastClientOpenedAt,
            'has_outstanding_client_items' => $this->hasOutstandingClientItems,
            'ready_to_complete' => $this->readyToComplete,
            'review_summary' => $this->reviewSummary->toArray(),
            'client' => $this->client->toArray(),
            'document_requests' => array_map(
                static fn (StaffDocumentRequestData $request): array => $request->toArray(),
                $this->documentRequests,
            ),
            'access_grants' => array_map(
                static fn (DossierAccessGrantData $grant): array => $grant->toArray(),
                $this->accessGrants,
            ),
        ];
    }
}
