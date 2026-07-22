<?php

declare(strict_types=1);

namespace App\Data\Dossiers;

/**
 * Active dossier index table row.
 */
final readonly class DossierIndexRowData
{
    public function __construct(
        public int $id,
        public string $clientName,
        public string $title,
        public ?string $reference,
        public string $status,
        public ?string $dueDate,
        public ?string $responsibleName,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     client_name: string,
     *     title: string,
     *     reference: string|null,
     *     status: string,
     *     due_date: string|null,
     *     responsible_name: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'client_name' => $this->clientName,
            'title' => $this->title,
            'reference' => $this->reference,
            'status' => $this->status,
            'due_date' => $this->dueDate,
            'responsible_name' => $this->responsibleName,
        ];
    }
}
