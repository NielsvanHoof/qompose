<?php

declare(strict_types=1);

namespace App\Actions\Dossiers;

use App\Models\Dossier;

/**
 * Persist a new dossier for the current tenant.
 */
final class CreateDossierAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): Dossier
    {
        return Dossier::query()->create($attributes);
    }
}
