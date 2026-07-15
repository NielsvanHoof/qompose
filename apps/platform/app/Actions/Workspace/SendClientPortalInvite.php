<?php

declare(strict_types=1);

namespace App\Actions\Workspace;

use App\Models\Client;
use App\Models\ClientAccessGrant;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Notifications\ClientPortalInviteNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use RuntimeException;

final class SendClientPortalInvite
{
    /**
     * Email the dossier's client a magic link that opens the portal with this grant.
     */
    public function __invoke(
        Dossier $dossier,
        ClientAccessGrant $grant,
        string $plainTextToken,
    ): void {
        $dossier->loadMissing(['client', 'tenant']);

        $client = $dossier->client;

        if (! $client instanceof Client) {
            throw new RuntimeException('Dossier client is missing.');
        }

        $firmName = $dossier->tenant instanceof Tenant
            ? $dossier->tenant->name
            : (string) config('app.name');

        // Absolute portal URL embedding the one-time plain token.
        $portalUrl = URL::route('portal.show', ['token' => $plainTextToken]);

        Notification::route('mail', $client->email)
            ->notify(new ClientPortalInviteNotification(
                dossier: $dossier,
                portalUrl: $portalUrl,
                expiresAt: $grant->expires_at,
                firmName: $firmName,
            ));
    }
}
