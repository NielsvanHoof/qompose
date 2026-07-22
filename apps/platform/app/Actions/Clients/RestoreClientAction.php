<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Actions\Audit\LogAuditActivityAction;
use App\Enums\AuditEvent;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class RestoreClientAction
{
    public function __construct(private readonly LogAuditActivityAction $logAuditActivity) {}

    public function handle(Client $client, User $restoredBy): void
    {
        DB::transaction(function () use ($client, $restoredBy): void {
            $clientQuery = Client::withTrashed()->whereKey($client->getKey());
            $clientQuery->getQuery()->lockForUpdate();
            $lockedClient = $clientQuery->firstOrFail();

            if (! $lockedClient->trashed()) {
                return;
            }

            $lockedClient->disableLogging();
            $lockedClient->forceFill(['deleted_at' => null])->saveOrFail();

            $this->logAuditActivity->handle(
                AuditEvent::ClientRestored,
                $lockedClient,
                [
                    'name' => $lockedClient->name,
                    'email' => $lockedClient->email,
                ],
                $restoredBy,
            );
        });
    }
}
