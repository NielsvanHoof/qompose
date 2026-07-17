<?php

declare(strict_types=1);

namespace App\Transitions;

use App\Enums\DossierStatus;
use App\Models\Dossier;
use Illuminate\Validation\ValidationException;

/**
 * Guarded status transitions for dossiers.
 *
 * Flow: draft → awaiting_client → in_review → completed.
 * Later open stages do not regress; completed is terminal.
 */
final class DossierTransitions
{
    public function markAwaitingClient(Dossier $dossier): void
    {
        if ($dossier->status === DossierStatus::Completed) {
            throw ValidationException::withMessages([
                'dossier' => 'A completed dossier cannot receive a new portal invitation.',
            ]);
        }

        // Already past draft (e.g. in review) — leave status alone.
        if ($dossier->status !== DossierStatus::Draft) {
            return;
        }

        $dossier->update(['status' => DossierStatus::AwaitingClient]);
    }

    public function markInReview(Dossier $dossier): void
    {
        if ($dossier->status === DossierStatus::Completed) {
            throw ValidationException::withMessages([
                'dossier' => 'A completed dossier cannot return to review.',
            ]);
        }

        if ($dossier->status === DossierStatus::InReview) {
            return;
        }

        $dossier->update(['status' => DossierStatus::InReview]);
    }

    public function complete(Dossier $dossier): void
    {
        if ($dossier->status === DossierStatus::Completed) {
            throw ValidationException::withMessages([
                'dossier' => 'This dossier is already completed.',
            ]);
        }

        if ($dossier->status !== DossierStatus::InReview) {
            throw ValidationException::withMessages([
                'dossier' => 'Only a dossier in review can be completed.',
            ]);
        }

        $dossier->update(['status' => DossierStatus::Completed]);
    }
}
