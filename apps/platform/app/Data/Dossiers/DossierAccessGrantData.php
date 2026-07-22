<?php

declare(strict_types=1);

namespace App\Data\Dossiers;

/**
 * Portal access-grant row on dossier show.
 */
final readonly class DossierAccessGrantData
{
    public function __construct(
        public int $id,
        public string $expiresAt,
        public ?string $revokedAt,
        public ?string $lastUsedAt,
        public bool $isValid,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     expires_at: string,
     *     revoked_at: string|null,
     *     last_used_at: string|null,
     *     is_valid: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'expires_at' => $this->expiresAt,
            'revoked_at' => $this->revokedAt,
            'last_used_at' => $this->lastUsedAt,
            'is_valid' => $this->isValid,
        ];
    }
}
