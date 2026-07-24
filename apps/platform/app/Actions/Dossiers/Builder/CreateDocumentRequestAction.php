<?php

declare(strict_types=1);

namespace App\Actions\Dossiers\Builder;

use App\Actions\Audit\LogAuditActivityAction;
use App\Enums\AuditEvent;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use Illuminate\Support\Facades\DB;

use function array_key_exists;

final class CreateDocumentRequestAction
{
    public function __construct(
        private readonly LogAuditActivityAction $logAuditActivity,
    ) {}

    /**
     * Create a questionnaire item and optionally insert it at a zero-based position.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Dossier $dossier, array $attributes): DocumentRequest
    {
        return DB::transaction(function () use ($dossier, $attributes): DocumentRequest {
            $dossierQuery = Dossier::query()->whereKey($dossier->getKey());
            $dossierQuery->getQuery()->lockForUpdate();
            $lockedDossier = $dossierQuery->firstOrFail();

            $position = array_key_exists('position', $attributes)
                ? $attributes['position']
                : null;
            unset($attributes['position']);

            $itemCount = $lockedDossier->documentRequests()->toBase()->count();

            // Append when no position is given. Otherwise clamp into the list.
            $insertAt = $position === null
                ? $itemCount
                : max(0, min((int) $position, $itemCount));

            if ($insertAt < $itemCount) {
                DocumentRequest::query()
                    ->where('dossier_id', $lockedDossier->getKey())
                    ->where('sort_order', '>=', $insertAt)
                    ->increment('sort_order');
            }

            $documentRequest = $lockedDossier->documentRequests()->create([
                ...$attributes,
                'sort_order' => $insertAt,
            ]);

            $this->logAuditActivity->handle(
                AuditEvent::DocumentRequestCreated,
                $documentRequest,
            );

            return $documentRequest;
        });
    }
}
