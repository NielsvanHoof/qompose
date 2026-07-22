<?php

declare(strict_types=1);

namespace App\Actions\Dossiers;

use App\Actions\Audit\LogAuditActivityAction;
use App\Actions\Portal\RevokeClientPortalAccessAction;
use App\Enums\AuditEvent;
use App\Models\ClientAccessGrant;
use App\Models\Dossier;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class DeleteDossierAction
{
    public function __construct(
        private readonly LogAuditActivityAction $logAuditActivity,
        private readonly RevokeClientPortalAccessAction $revokeClientPortalAccess,
    ) {}

    public function handle(Dossier $dossier, User $deletedBy): void
    {
        DB::transaction(function () use ($dossier, $deletedBy): void {
            $dossierQuery = Dossier::query()->whereKey($dossier->getKey());
            $dossierQuery->getQuery()->lockForUpdate();
            $lockedDossier = $dossierQuery->firstOrFail();

            if (! $lockedDossier->trashed()) {
                ClientAccessGrant::query()
                    ->where('dossier_id', $lockedDossier->id)
                    ->where('revoked_at', null)
                    ->where('expires_at', '>', now())
                    ->get()
                    ->each(function (ClientAccessGrant $grant) use ($deletedBy): void {
                        $this->revokeClientPortalAccess->handle($grant, $deletedBy);
                    });

                $this->logAuditActivity->handle(
                    AuditEvent::DossierDeleted,
                    $lockedDossier,
                    [
                        'title' => $lockedDossier->title,
                        'reference' => $lockedDossier->reference,
                        'status' => $lockedDossier->status->value,
                    ],
                    $deletedBy,
                );

                $lockedDossier->disableLogging();
                $lockedDossier->forceFill(['next_reminder_at' => null])->save();
                $lockedDossier->deleteOrFail();
            }
        });
    }
}
