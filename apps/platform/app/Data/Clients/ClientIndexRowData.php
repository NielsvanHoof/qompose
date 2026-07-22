<?php

declare(strict_types=1);

namespace App\Data\Clients;

/**
 * Client index table row.
 */
final readonly class ClientIndexRowData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public int $dossiersCount,
    ) {}

    /**
     * @return array{id: int, name: string, email: string, dossiers_count: int}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'dossiers_count' => $this->dossiersCount,
        ];
    }
}
