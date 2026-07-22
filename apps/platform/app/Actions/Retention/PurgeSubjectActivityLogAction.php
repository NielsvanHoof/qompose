<?php

declare(strict_types=1);

namespace App\Actions\Retention;

use App\Models\Activity;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\UploadedDocument;
use Illuminate\Database\Eloquent\Model;

final class PurgeSubjectActivityLogAction
{
    public function handle(Model $subject): int
    {
        if ($subject instanceof Dossier) {
            return $this->purgeDossierActivity($subject);
        }

        return $this->purgeForModel($subject);
    }

    private function purgeDossierActivity(Dossier $dossier): int
    {
        $deletedCount = $this->purgeForModel($dossier);

        $documentRequestIds = DocumentRequest::query()
            ->where('dossier_id', $dossier->id)
            ->pluck('id');

        foreach ($documentRequestIds as $documentRequestId) {
            $deletedCount += Activity::query()
                ->where('subject_type', DocumentRequest::class)
                ->where('subject_id', $documentRequestId)
                ->delete();
        }

        $uploadedDocumentIds = UploadedDocument::query()
            ->whereIn('document_request_id', $documentRequestIds)
            ->pluck('id');

        foreach ($uploadedDocumentIds as $uploadedDocumentId) {
            $deletedCount += Activity::query()
                ->where('subject_type', UploadedDocument::class)
                ->where('subject_id', $uploadedDocumentId)
                ->delete();
        }

        return $deletedCount;
    }

    private function purgeForModel(Model $subject): int
    {
        return Activity::query()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->delete();
    }
}
