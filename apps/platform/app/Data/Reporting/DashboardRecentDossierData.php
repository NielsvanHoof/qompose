<?php

declare(strict_types=1);

namespace App\Data\Reporting;

/**
 * Recent dossier card on the workspace dashboard.
 */
final readonly class DashboardRecentDossierData
{
    public function __construct(
        public int $id,
        public string $title,
        public ?string $reference,
        public string $status,
        public string $clientName,
        public ?string $dueDate,
        public ?string $responsibleName,
        public string $updatedAt,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     title: string,
     *     reference: string|null,
     *     status: string,
     *     client_name: string,
     *     due_date: string|null,
     *     responsible_name: string|null,
     *     updated_at: string
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'reference' => $this->reference,
            'status' => $this->status,
            'client_name' => $this->clientName,
            'due_date' => $this->dueDate,
            'responsible_name' => $this->responsibleName,
            'updated_at' => $this->updatedAt,
        ];
    }
}
