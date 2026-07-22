<?php

declare(strict_types=1);

namespace App\Actions\Retention;

use App\Models\Dossier;
use App\Models\UploadedDocument;
use Illuminate\Support\Facades\DB;
use Throwable;

final class PurgeExpiredDossierAction
{
    public function __construct(
        private readonly DeleteStoredDocumentFilesAction $deleteStoredDocumentFiles,
        private readonly PurgeSubjectActivityLogAction $purgeSubjectActivityLog,
    ) {}

    /**
     * @return array{purged: bool, activity_rows_deleted: int, files_deleted: int}
     */
    public function handle(Dossier $dossier): array
    {
        if (! $dossier->trashed()) {
            return [
                'purged' => false,
                'activity_rows_deleted' => 0,
                'files_deleted' => 0,
            ];
        }

        $uploadedDocuments = UploadedDocument::query()
            ->whereIn(
                'document_request_id',
                $dossier->documentRequests()->pluck('id'),
            )
            ->get();

        try {
            $this->deleteStoredDocumentFiles->handle($uploadedDocuments);
        } catch (Throwable) {
            return [
                'purged' => false,
                'activity_rows_deleted' => 0,
                'files_deleted' => 0,
            ];
        }

        return DB::transaction(function () use ($dossier, $uploadedDocuments): array {
            $activityRowsDeleted = $this->purgeSubjectActivityLog->handle($dossier);

            $dossier->disableLogging();
            $dossier->forceDelete();

            return [
                'purged' => true,
                'activity_rows_deleted' => $activityRowsDeleted,
                'files_deleted' => $uploadedDocuments->count(),
            ];
        });
    }
}
