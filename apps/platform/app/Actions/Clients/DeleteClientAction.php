<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Actions\Audit\LogAuditActivityAction;
use App\Actions\Dossiers\DeleteDossierAction;
use App\Enums\AuditEvent;
use App\Models\Client;
use App\Models\Dossier;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class DeleteClientAction
{
    public function __construct(
        private readonly DeleteDossierAction $deleteDossier,
        private readonly LogAuditActivityAction $logAuditActivity,
    ) {}

    public function handle(Client $client, User $deletedBy): void
    {
        DB::transaction(function () use ($client, $deletedBy): void {
            $clientQuery = Client::query()->whereKey($client->getKey());
            $clientQuery->getQuery()->lockForUpdate();
            $lockedClient = $clientQuery->firstOrFail();

            if (! $lockedClient->trashed()) {
                Dossier::query()
                    ->where('client_id', $lockedClient->id)
                    ->get()
                    ->each(function (Dossier $dossier) use ($deletedBy): void {
                        $this->deleteDossier->handle($dossier, $deletedBy);
                    });

                $this->logAuditActivity->handle(
                    AuditEvent::ClientDeleted,
                    $lockedClient,
                    [
                        'name' => $lockedClient->name,
                        'email' => $lockedClient->email,
                    ],
                    $deletedBy,
                );

                $lockedClient->disableLogging();
                $lockedClient->deleteOrFail();
            }
        });
    }
}
