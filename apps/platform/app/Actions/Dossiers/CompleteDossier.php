<?php

declare(strict_types=1);

namespace App\Actions\Dossiers;

use App\Actions\Audit\LogAuditActivity;
use App\Enums\AuditEvent;
use App\Enums\DocumentRequestStatus;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\User;
use App\Transitions\DossierTransitions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CompleteDossier
{
    public function __construct(
        private readonly LogAuditActivity $logAuditActivity,
        private readonly DossierTransitions $dossierTransitions,
    ) {}

    public function handle(Dossier $dossier, User $completedBy): Dossier
    {
        return DB::transaction(function () use ($dossier, $completedBy): Dossier {
            $dossierQuery = Dossier::query()->whereKey($dossier->getKey());
            $dossierQuery->getQuery()->lockForUpdate();
            $lockedDossier = $dossierQuery->firstOrFail();

            $documentRequestQuery = DocumentRequest::query()
                ->whereBelongsTo($lockedDossier)
                ->oldest('id');
            $documentRequestQuery->getQuery()->lockForUpdate();

            $documentRequests = $documentRequestQuery->get(['id', 'status']);
            $requestCount = $documentRequests->count();
            $hasUnacceptedRequest = $documentRequests->contains(
                fn (DocumentRequest $documentRequest): bool => $documentRequest->status
                    !== DocumentRequestStatus::Accepted,
            );

            if ($requestCount === 0 || $hasUnacceptedRequest) {
                throw ValidationException::withMessages([
                    'dossier' => 'Every questionnaire item must be approved before completing the dossier.',
                ]);
            }

            $this->dossierTransitions->complete($lockedDossier);

            $this->logAuditActivity->handle(
                AuditEvent::DossierCompleted,
                $lockedDossier,
                ['document_request_count' => $requestCount],
                $completedBy,
            );

            return $lockedDossier->fresh() ?? $lockedDossier;
        });
    }
}
