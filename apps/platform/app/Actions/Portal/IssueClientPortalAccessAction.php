<?php

declare(strict_types=1);

namespace App\Actions\Portal;

use App\Actions\Audit\LogAuditActivityAction;
use App\Enums\AuditEvent;
use App\Models\ClientAccessGrant;
use App\Models\Dossier;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class IssueClientPortalAccessAction
{
    public function __construct(
        private readonly CreateClientAccessGrantAction $createClientAccessGrant,
        private readonly SendClientPortalInviteAction $sendClientPortalInvite,
        private readonly LogAuditActivityAction $logAuditActivity,
    ) {}

    /**
     * @return array{grant: ClientAccessGrant, plain_text_token: string}
     */
    public function handle(
        Dossier $dossier,
        User $createdBy,
        int $expiresInDays,
        bool $sendInvite,
    ): array {
        return DB::transaction(function () use (
            $dossier,
            $createdBy,
            $expiresInDays,
            $sendInvite,
        ): array {
            $result = $this->createClientAccessGrant->handle(
                $dossier,
                $createdBy,
                $expiresInDays,
            );

            $this->logAuditActivity->handle(
                AuditEvent::ClientPortalAccessGrantCreated,
                $result['grant'],
                [
                    'dossier_id' => $dossier->id,
                    'expires_at' => $result['grant']->expires_at->toIso8601String(),
                    'invite_requested' => $sendInvite,
                ],
                $createdBy,
            );

            if ($sendInvite) {
                $this->sendClientPortalInvite->handle(
                    $dossier,
                    $result['grant'],
                    $result['plain_text_token'],
                );

                $this->logAuditActivity->handle(
                    AuditEvent::ClientPortalInviteQueued,
                    $result['grant'],
                    [
                        'dossier_id' => $dossier->id,
                        'channel' => 'mail',
                    ],
                    $createdBy,
                );
            }

            return $result;
        });
    }
}
