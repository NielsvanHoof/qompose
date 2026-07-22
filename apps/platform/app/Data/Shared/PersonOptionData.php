<?php

declare(strict_types=1);

namespace App\Data\Shared;

/**
 * Reusable {id, name, email} option for selects (staff, clients, …).
 */
final readonly class PersonOptionData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
    ) {}

    /**
     * @return array{id: int, name: string, email: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
