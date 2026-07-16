<?php

declare(strict_types=1);

namespace App\Actions\Dossiers;

use App\Actions\Audit\LogAuditActivity;
use App\Enums\AuditEvent;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use Illuminate\Support\Facades\DB;

final class CreateDocumentRequest
{
    public function __construct(
        private readonly LogAuditActivity $logAuditActivity,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Dossier $dossier, array $attributes): DocumentRequest
    {
        return DB::transaction(function () use ($dossier, $attributes): DocumentRequest {
            $dossierQuery = Dossier::query()->whereKey($dossier->getKey());
            $dossierQuery->getQuery()->lockForUpdate();
            $lockedDossier = $dossierQuery->firstOrFail();

            $nextSortOrder = (int) $lockedDossier
                ->documentRequests()
                ->toBase()
                ->max('sort_order') + 1;

            $documentRequest = $lockedDossier->documentRequests()->create([
                ...$attributes,
                'sort_order' => $nextSortOrder,
            ]);

            $this->logAuditActivity->handle(
                AuditEvent::DocumentRequestCreated,
                $documentRequest,
            );

            return $documentRequest;
        });
    }
}
