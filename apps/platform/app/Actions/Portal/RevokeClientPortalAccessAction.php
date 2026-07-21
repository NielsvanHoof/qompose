<?php

declare(strict_types=1);

namespace App\Actions\Portal;

use App\Actions\Audit\LogAuditActivityAction;
use App\Enums\AuditEvent;
use App\Models\ClientAccessGrant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class RevokeClientPortalAccessAction
{
    public function __construct(
        private readonly LogAuditActivityAction $logAuditActivity,
    ) {}

    public function handle(ClientAccessGrant $grant, User $revokedBy): ClientAccessGrant
    {
        return DB::transaction(function () use ($grant, $revokedBy): ClientAccessGrant {
            $grantQuery = ClientAccessGrant::query()->whereKey($grant->getKey());
            $grantQuery->getQuery()->lockForUpdate();
            $lockedGrant = $grantQuery->firstOrFail();

            if ($lockedGrant->revoked_at !== null) {
                return $lockedGrant;
            }

            $lockedGrant->update(['revoked_at' => now()]);

            $this->logAuditActivity->handle(
                AuditEvent::ClientPortalAccessGrantRevoked,
                $lockedGrant,
                ['dossier_id' => $lockedGrant->dossier_id],
                $revokedBy,
            );

            return $lockedGrant;
        });
    }
}
