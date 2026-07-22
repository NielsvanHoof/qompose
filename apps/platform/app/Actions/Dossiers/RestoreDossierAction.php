<?php

declare(strict_types=1);

namespace App\Actions\Dossiers;

use App\Actions\Audit\LogAuditActivityAction;
use App\Enums\AuditEvent;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RestoreDossierAction
{
    public function __construct(private readonly LogAuditActivityAction $logAuditActivity) {}

    public function handle(Dossier $dossier, User $restoredBy): void
    {
        DB::transaction(function () use ($dossier, $restoredBy): void {
            $dossierQuery = Dossier::withTrashed()->whereKey($dossier->getKey());
            $dossierQuery->getQuery()->lockForUpdate();
            $lockedDossier = $dossierQuery->firstOrFail();

            if (! $lockedDossier->trashed()) {
                return;
            }

            $client = Client::withTrashed()->findOrFail($lockedDossier->client_id);

            if ($client->trashed()) {
                throw ValidationException::withMessages([
                    'dossier' => __('Restore the client before restoring this dossier.'),
                ]);
            }

            $lockedDossier->disableLogging();
            $lockedDossier->forceFill(['deleted_at' => null])->saveOrFail();

            $this->logAuditActivity->handle(
                AuditEvent::DossierRestored,
                $lockedDossier,
                [
                    'title' => $lockedDossier->title,
                    'reference' => $lockedDossier->reference,
                    'status' => $lockedDossier->status->value,
                ],
                $restoredBy,
            );
        });
    }
}
