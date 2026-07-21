<?php

declare(strict_types=1);

namespace App\Actions\Portal;

use App\Actions\Audit\LogAuditActivity;
use App\Enums\AuditEvent;
use App\Events\Portal\ClientQuestionnaireCompleted;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\Portal\ClientQuestionnaireCompletedNotification;
use App\Queries\Dossiers\DossierHasOutstandingClientItems;
use App\Queries\Tenancy\GetActiveUsersForTenant;
use RuntimeException;

/**
 * When every questionnaire item is submitted, notify workspace staff in real time.
 */
final class NotifyWorkspaceIfQuestionnaireComplete
{
    public function __construct(
        private readonly DossierHasOutstandingClientItems $dossierHasOutstandingClientItems,
        private readonly LogAuditActivity $logAuditActivity,
        private readonly GetActiveUsersForTenant $getActiveUsersForTenant,
    ) {}

    public function handle(Dossier $dossier): void
    {
        // Empty dossiers never count as complete.
        if (! $dossier->documentRequests()->toBase()->exists()) {
            return;
        }

        if ($this->dossierHasOutstandingClientItems->handle($dossier)) {
            return;
        }

        $dossier->loadMissing(['client', 'tenant']);

        $client = $dossier->client;

        if (! $client instanceof Client) {
            throw new RuntimeException('Dossier client is missing.');
        }

        $tenant = $dossier->tenant;

        if (! $tenant instanceof Tenant) {
            throw new RuntimeException('Dossier tenant is missing.');
        }

        $message = __(':client finished the questionnaire for “:dossier”.', [
            'client' => $client->name,
            'dossier' => $dossier->title,
        ]);

        $dossierUrl = route('workspaces.dossiers.show', [
            'tenant' => $tenant,
            'dossier' => $dossier,
        ]);

        $this->logAuditActivity->handle(
            AuditEvent::ClientQuestionnaireCompleted,
            $dossier,
            [
                'source' => 'client_portal',
                'client_id' => $client->id,
            ],
        );

        // Persist inbox rows before the broadcast so the Echo-triggered reload sees them.
        $notification = new ClientQuestionnaireCompletedNotification(
            tenantId: $tenant->id,
            dossierId: $dossier->id,
            dossierTitle: $dossier->title,
            clientName: $client->name,
            message: $message,
            dossierUrl: $dossierUrl,
        );

        $this->getActiveUsersForTenant->handle($tenant)->each(
            fn (User $user) => $user->notify($notification),
        );

        ClientQuestionnaireCompleted::dispatch(
            $tenant->slug,
            $dossier->id,
            $dossier->title,
            $client->name,
            $message,
            $dossierUrl,
        );
    }
}
