<?php

declare(strict_types=1);

namespace App\Queries\Dossiers;

use App\Models\Dossier;

/**
 * True when the current tenant already has at least one dossier.
 * Used by Clients to decide onboarding redirects without touching Dossier models.
 */
final class TenantHasAnyDossiersQuery
{
    public function handle(): bool
    {
        return Dossier::query()->toBase()->exists();
    }
}
