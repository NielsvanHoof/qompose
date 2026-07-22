<?php

declare(strict_types=1);

namespace App\Data\Clients;

/**
 * Dossier row nested under the client show page.
 */
final readonly class ClientDossierRowData
{
    public function __construct(
        public int $id,
        public string $title,
        public ?string $reference,
        public string $status,
        public string $clientName,
        public string $updatedAt,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     title: string,
     *     reference: string|null,
     *     status: string,
     *     client_name: string,
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
            'updated_at' => $this->updatedAt,
        ];
    }
}
