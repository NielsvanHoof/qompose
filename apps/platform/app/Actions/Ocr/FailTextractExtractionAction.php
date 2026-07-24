<?php

declare(strict_types=1);

namespace App\Actions\Ocr;

use App\Enums\DocumentProcessingStatus;
use App\Models\Tenant;
use App\Models\UploadedDocument;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\Log;
use Throwable;

use function in_array;

/**
 * Mark a Textract job as permanently failed after SQS receive exhaustion.
 */
final class FailTextractExtractionAction
{
    public function __construct(
        private readonly MarkTextractExtractionFailedAction $markTextractExtractionFailed,
        private readonly TenantContext $tenantContext,
    ) {}

    public function handle(string $jobId, Throwable $exception): bool
    {
        $document = UploadedDocument::query()
            ->withoutGlobalScopes()
            ->where('textract_job_id', $jobId)
            ->first();

        if (! $document instanceof UploadedDocument) {
            Log::warning('OCR: permanent completion failure has no matching uploaded document.', [
                'textract_job_id' => $jobId,
            ]);

            return false;
        }

        $tenant = Tenant::query()->find($document->tenant_id);

        if (! $tenant instanceof Tenant) {
            Log::error('OCR: permanent completion failure document has missing tenant.', [
                'uploaded_document_id' => $document->id,
                'tenant_id' => $document->tenant_id,
                'textract_job_id' => $jobId,
            ]);

            return false;
        }

        return $this->tenantContext->runForTenant($tenant, function () use ($document, $exception, $jobId): bool {
            if (in_array($document->processing_status, [
                DocumentProcessingStatus::Completed,
                DocumentProcessingStatus::Failed,
            ], true)) {
                return true;
            }

            $error = mb_substr($exception->getMessage(), 0, 500);

            if ($error === '') {
                $error = 'Textract result processing failed permanently.';
            }

            $this->markTextractExtractionFailed->handle(
                $document,
                $jobId,
                $error,
                'RESULT_PROCESSING_FAILED',
            );

            return true;
        });
    }
}
