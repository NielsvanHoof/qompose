<?php

declare(strict_types=1);

namespace App\Data\Portal;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Full client portal Inertia payload.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class ClientPortalPageData implements Arrayable
{
    public function __construct(
        public PortalFirmData $firm,
        public PortalDossierData $dossier,
    ) {}

    /**
     * @return array{
     *     firm: array{name: string},
     *     dossier: array<string, mixed>
     * }
     */
    public function toArray(): array
    {
        return [
            'firm' => $this->firm->toArray(),
            'dossier' => $this->dossier->toArray(),
        ];
    }
}
