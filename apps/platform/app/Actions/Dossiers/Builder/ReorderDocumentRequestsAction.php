<?php

declare(strict_types=1);

namespace App\Actions\Dossiers\Builder;

use App\Models\DocumentRequest;
use App\Models\Dossier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use function array_diff;
use function array_unique;
use function count;

final class ReorderDocumentRequestsAction
{
    /**
     * @param  array<mixed>  $documentRequestIds
     */
    public function handle(Dossier $dossier, array $documentRequestIds): void
    {
        $ids = array_map(
            static fn (mixed $id): int => (int) $id,
            $documentRequestIds,
        );

        DB::transaction(function () use ($dossier, $ids): void {
            $dossierQuery = Dossier::query()->whereKey($dossier->getKey());
            $dossierQuery->getQuery()->lockForUpdate();
            $lockedDossier = $dossierQuery->firstOrFail();

            $ownedIds = $lockedDossier->documentRequests()
                ->toBase()
                ->pluck('id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->all();

            if (count($ids) !== count(array_unique($ids))
                || count($ids) !== count($ownedIds)
                || array_diff($ids, $ownedIds) !== []
                || array_diff($ownedIds, $ids) !== []) {
                throw ValidationException::withMessages([
                    'document_request_ids' => 'Submit every document request in this dossier exactly once.',
                ]);
            }

            foreach ($ids as $index => $id) {
                DocumentRequest::query()
                    ->whereKey($id)
                    ->update(['sort_order' => $index]);
            }
        });
    }
}
