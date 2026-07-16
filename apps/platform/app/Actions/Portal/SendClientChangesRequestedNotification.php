<?php

declare(strict_types=1);

namespace App\Actions\Portal;

use App\Models\Client;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Notifications\Portal\ClientChangesRequestedNotification;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

final class SendClientChangesRequestedNotification
{
    public function handle(DocumentRequest $documentRequest): void
    {
        $documentRequest->loadMissing([
            'dossier.client',
            'dossier.tenant',
        ]);

        $dossier = $documentRequest->dossier;

        if (! $dossier instanceof Dossier) {
            throw new RuntimeException('Document request dossier is missing.');
        }

        $client = $dossier->client;

        if (! $client instanceof Client) {
            throw new RuntimeException('Dossier client is missing.');
        }

        $firmName = $dossier->tenant instanceof Tenant
            ? $dossier->tenant->name
            : (string) config('app.name');

        Notification::route('mail', $client->email)
            ->notify(new ClientChangesRequestedNotification(
                documentRequestId: $documentRequest->id,
                dossierId: $dossier->id,
                clientName: $client->name,
                dossierTitle: $dossier->title,
                documentRequestTitle: $documentRequest->title,
                firmName: $firmName,
            ));
    }
}
