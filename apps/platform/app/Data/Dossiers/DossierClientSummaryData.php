<?php

declare(strict_types=1);

namespace App\Data\Dossiers;

/**
 * Client name/email embedded on dossier show.
 */
final readonly class DossierClientSummaryData
{
    public function __construct(
        public string $name,
        public string $email,
    ) {}

    /**
     * @return array{name: string, email: string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
