<?php

declare(strict_types=1);

namespace App\Data\Clients;

/**
 * Client header summary on the client show page.
 */
final readonly class ClientSummaryData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public int $activeDossiersCount,
        public int $archivedDossiersCount,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     email: string,
     *     active_dossiers_count: int,
     *     archived_dossiers_count: int
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'active_dossiers_count' => $this->activeDossiersCount,
            'archived_dossiers_count' => $this->archivedDossiersCount,
        ];
    }
}
