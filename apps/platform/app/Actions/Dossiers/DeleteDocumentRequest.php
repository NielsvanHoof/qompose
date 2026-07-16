<?php

declare(strict_types=1);

namespace App\Actions\Dossiers;

use App\Models\DocumentRequest;
use App\Models\UploadedDocument;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

final class DeleteDocumentRequest
{
    public function __construct(
        private readonly FilesystemManager $filesystems,
    ) {}

    public function __invoke(DocumentRequest $documentRequest): void
    {
        /** @var array{disk: string, path: string}|null $storedFile */
        $storedFile = DB::transaction(function () use ($documentRequest): ?array {
            $documentRequestQuery = DocumentRequest::query()
                ->whereKey($documentRequest->getKey());
            $documentRequestQuery->getQuery()->lockForUpdate();

            $lockedDocumentRequest = $documentRequestQuery->firstOrFail();
            $uploadedDocument = UploadedDocument::query()
                ->where('document_request_id', $lockedDocumentRequest->id)
                ->first();
            $storedFile = $uploadedDocument instanceof UploadedDocument
                ? [
                    'disk' => $uploadedDocument->disk,
                    'path' => $uploadedDocument->path,
                ]
                : null;

            $lockedDocumentRequest->deleteOrFail();

            return $storedFile;
        });

        if ($storedFile === null) {
            return;
        }

        try {
            $deleted = $this->filesystems
                ->disk($storedFile['disk'])
                ->delete($storedFile['path']);
        } catch (Throwable $exception) {
            report(new RuntimeException(
                'Failed to remove the stored file for a deleted document request.',
                previous: $exception,
            ));

            return;
        }

        if (! $deleted) {
            report(new RuntimeException(
                'Failed to remove the stored file for a deleted document request.',
            ));
        }
    }
}
