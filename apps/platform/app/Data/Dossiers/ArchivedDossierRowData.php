<?php

declare(strict_types=1);

namespace App\Data\Dossiers;

/**
 * Archived dossier index table row.
 */
final readonly class ArchivedDossierRowData
{
    public function __construct(
        public int $id,
        public string $title,
        public ?string $reference,
        public string $status,
        public string $clientName,
        public bool $clientArchived,
        public string $archivedAt,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     title: string,
     *     reference: string|null,
     *     status: string,
     *     client_name: string,
     *     client_archived: bool,
     *     archived_at: string
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
            'client_archived' => $this->clientArchived,
            'archived_at' => $this->archivedAt,
        ];
    }
}
