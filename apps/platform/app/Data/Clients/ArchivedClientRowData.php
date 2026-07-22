<?php

declare(strict_types=1);

namespace App\Data\Clients;

/**
 * Archived client index row.
 */
final readonly class ArchivedClientRowData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public int $dossiersCount,
        public string $archivedAt,
    ) {}

    /**
     * @return array{id: int, name: string, email: string, dossiers_count: int, archived_at: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'dossiers_count' => $this->dossiersCount,
            'archived_at' => $this->archivedAt,
        ];
    }
}
