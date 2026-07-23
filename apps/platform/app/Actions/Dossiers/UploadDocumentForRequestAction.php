<?php

declare(strict_types=1);

namespace App\Actions\Dossiers;

use App\Enums\DocumentProcessingStatus;
use App\Enums\QuestionnaireItemType;
use App\Enums\SubmissionContext;
use App\Jobs\ProcessUploadedDocumentJob;
use App\Models\DocumentRequest;
use App\Models\UploadedDocument;
use App\Transitions\DocumentRequestTransitions;
use Closure;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

use function is_int;
use function is_string;
use function sprintf;

final class UploadDocumentForRequestAction
{
    public function __construct(
        private readonly FilesystemManager $filesystems,
        private readonly DocumentRequestTransitions $documentRequestTransitions,
    ) {}

    /**
     * Store a file for a document request and mark the request as submitted.
     * Replaces any previous upload for the same request.
     *
     * @param  (Closure(UploadedDocument, DocumentRequest): void)|null  $afterPersist
     */
    public function handle(
        DocumentRequest $documentRequest,
        UploadedFile $file,
        ?Closure $afterPersist = null,
        SubmissionContext $context = SubmissionContext::Staff,
    ): UploadedDocument {
        if ($documentRequest->type !== QuestionnaireItemType::File) {
            throw new InvalidArgumentException('Only file items accept uploads.');
        }

        $disk = (string) config('filesystems.default', 'local');
        $directory = sprintf(
            'tenants/%d/dossiers/%d',
            $documentRequest->tenant_id,
            $documentRequest->dossier_id,
        );

        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid()->toString().($extension !== '' ? '.'.$extension : '');
        $path = $this->filesystems
            ->disk($disk)
            ->putFileAs($directory, $file, $filename);

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('Failed to store the uploaded document.');
        }

        $sizeBytes = $file->getSize();

        /** @var array{disk: string, path: string}|null $replacedFile */
        $replacedFile = null;

        try {
            $uploadedDocument = DB::transaction(function () use (
                $documentRequest,
                $disk,
                $path,
                $file,
                $sizeBytes,
                $afterPersist,
                $context,
                &$replacedFile,
            ): UploadedDocument {
                $documentRequestQuery = DocumentRequest::query()
                    ->whereKey($documentRequest->getKey());
                $documentRequestQuery->getQuery()->lockForUpdate();

                $lockedDocumentRequest = $documentRequestQuery->firstOrFail();
                $existing = UploadedDocument::query()
                    ->where('document_request_id', $lockedDocumentRequest->id)
                    ->first();

                $attributes = [
                    'disk' => $disk,
                    'path' => $path,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                    'size_bytes' => is_int($sizeBytes) ? $sizeBytes : 0,
                    'uploaded_at' => now(),
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'rejection_reason' => null,
                    'processing_status' => DocumentProcessingStatus::Pending,
                    'extracted_text' => null,
                    'processing_error' => null,
                    'processing_started_at' => null,
                    'processing_finished_at' => null,
                ];

                if ($existing instanceof UploadedDocument) {
                    $replacedFile = [
                        'disk' => $existing->disk,
                        'path' => $existing->path,
                    ];

                    $existing->update($attributes);
                    $uploadedDocument = $existing;
                } else {
                    $uploadedDocument = $lockedDocumentRequest
                        ->uploadedDocument()
                        ->create($attributes);
                }

                $this->documentRequestTransitions->submitUpload($lockedDocumentRequest, $context);

                if ($afterPersist instanceof Closure) {
                    $afterPersist($uploadedDocument, $lockedDocumentRequest);
                }

                return $uploadedDocument;
            });
        } catch (Throwable $exception) {
            $this->deleteFileWithoutMaskingFailure(
                $disk,
                $path,
                'Failed to remove a newly uploaded document after its database transaction rolled back.',
            );

            throw $exception;
        }

        if ($replacedFile !== null && $replacedFile['path'] !== $path) {
            $this->deleteFileWithoutMaskingFailure(
                $replacedFile['disk'],
                $replacedFile['path'],
                'Failed to remove the file replaced by a newer document upload.',
            );
        }

        ProcessUploadedDocumentJob::dispatch($uploadedDocument->id);

        return $uploadedDocument;
    }

    private function deleteFileWithoutMaskingFailure(
        string $disk,
        string $path,
        string $message,
    ): void {
        try {
            $deleted = $this->filesystems->disk($disk)->delete($path);
        } catch (Throwable $exception) {
            report(new RuntimeException($message, previous: $exception));

            return;
        }

        if (! $deleted) {
            report(new RuntimeException($message));
        }
    }
}
