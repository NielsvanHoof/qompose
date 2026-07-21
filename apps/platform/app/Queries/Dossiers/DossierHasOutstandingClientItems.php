<?php

declare(strict_types=1);

namespace App\Queries\Dossiers;

use App\Enums\DocumentRequestStatus;
use App\Models\Dossier;

/**
 * True when the dossier still has questionnaire items awaiting client input.
 */
final class DossierHasOutstandingClientItems
{
    public function handle(Dossier $dossier): bool
    {
        return $dossier->documentRequests()
            ->whereIn('status', [
                DocumentRequestStatus::Pending,
                DocumentRequestStatus::Rejected,
            ])
            ->exists();
    }
}
