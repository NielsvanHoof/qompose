<?php

declare(strict_types=1);

namespace App\Actions\Dossiers;

use App\Actions\Audit\LogAuditActivity;
use App\Enums\AuditEvent;
use App\Enums\DocumentRequestStatus;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\QuestionnaireTemplate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ApplyQuestionnaireTemplateToDossier
{
    public function __construct(
        private readonly LogAuditActivity $logAuditActivity,
    ) {}

    /**
     * Append template items as document requests. Never removes existing rows.
     *
     * @return Collection<int, DocumentRequest>
     */
    public function handle(Dossier $dossier, QuestionnaireTemplate $template): Collection
    {
        $template->loadMissing('items');

        return DB::transaction(function () use ($dossier, $template): Collection {
            $dossierQuery = Dossier::query()->whereKey($dossier->getKey());
            $dossierQuery->getQuery()->lockForUpdate();
            $lockedDossier = $dossierQuery->firstOrFail();

            $nextSortOrder = (int) $lockedDossier
                ->documentRequests()
                ->toBase()
                ->max('sort_order') + 1;

            $created = collect();

            foreach ($template->items as $item) {
                $documentRequest = $lockedDossier->documentRequests()->create([
                    'type' => $item->type,
                    'title' => $item->title,
                    'instructions' => $item->instructions,
                    'status' => DocumentRequestStatus::Pending,
                    'sort_order' => $nextSortOrder,
                ]);

                $this->logAuditActivity->handle(
                    AuditEvent::DocumentRequestCreated,
                    $documentRequest,
                );

                $created->push($documentRequest);
                $nextSortOrder++;
            }

            return $created;
        });
    }
}
