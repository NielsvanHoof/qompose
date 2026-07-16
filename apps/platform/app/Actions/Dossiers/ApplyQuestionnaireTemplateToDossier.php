<?php

declare(strict_types=1);

namespace App\Actions\Dossiers;

use App\Enums\DocumentRequestStatus;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\QuestionnaireTemplate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ApplyQuestionnaireTemplateToDossier
{
    /**
     * Append template items as document requests. Never removes existing rows.
     *
     * @return Collection<int, DocumentRequest>
     */
    public function __invoke(Dossier $dossier, QuestionnaireTemplate $template): Collection
    {
        $template->loadMissing('items');

        return DB::transaction(function () use ($dossier, $template): Collection {
            // Aggregates live on the base query builder for strict PHPStan.
            $nextSortOrder = (int) $dossier->documentRequests()->toBase()->max('sort_order') + 1;

            $created = collect();

            foreach ($template->items as $item) {
                $documentRequest = $dossier->documentRequests()->create([
                    'type' => $item->type,
                    'title' => $item->title,
                    'instructions' => $item->instructions,
                    'status' => DocumentRequestStatus::Pending,
                    'sort_order' => $nextSortOrder,
                ]);

                $created->push($documentRequest);
                $nextSortOrder++;
            }

            return $created;
        });
    }
}
