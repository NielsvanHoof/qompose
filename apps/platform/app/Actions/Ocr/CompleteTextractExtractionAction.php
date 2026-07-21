<?php

declare(strict_types=1);

namespace App\Actions\Ocr;

use App\Actions\Audit\LogAuditActivityAction;
use App\Enums\AuditEvent;
use App\Enums\DocumentProcessingStatus;
use App\Models\Tenant;
use App\Models\UploadedDocument;
use App\Services\Ocr\Normalization\TextractAnalyzeDocumentNormalizer;
use App\Tenancy\TenantContext;
use Aws\Textract\TextractClient;
use JsonException;
use RuntimeException;

use function is_array;
use function is_string;
use function json_encode;

/**
 * Applies a Textract AnalyzeDocument SNS completion to an UploadedDocument row.
 * Stores normalized FORMS/TABLES JSON in extracted_text.
 */
final class CompleteTextractExtractionAction
{
    public function __construct(
        private readonly TextractClient $textract,
        private readonly TextractAnalyzeDocumentNormalizer $normalizer,
        private readonly LogAuditActivityAction $logAuditActivity,
        private readonly TenantContext $tenantContext,
    ) {}

    /**
     * @param  array{JobId?: mixed, Status?: mixed, StatusMessage?: mixed, API?: mixed}  $notification
     */
    public function handle(array $notification): bool
    {
        $jobId = $notification['JobId'] ?? null;
        $status = $notification['Status'] ?? null;

        if (! is_string($jobId) || $jobId === '') {
            return false;
        }

        if (! is_string($status) || $status === '') {
            return false;
        }

        // Ignore other Textract APIs if the SNS topic is shared later.
        $api = $notification['API'] ?? null;
        if (is_string($api) && $api !== '' && $api !== 'StartDocumentAnalysis') {
            return false;
        }

        $document = UploadedDocument::query()
            ->withoutGlobalScopes()
            ->where('textract_job_id', $jobId)
            ->first();

        if (! $document instanceof UploadedDocument) {
            return false;
        }

        // SQS consumer has no HTTP tenant context — switch for audits and scoped queries.
        $tenant = Tenant::query()->find($document->tenant_id);
        if (! $tenant instanceof Tenant) {
            return false;
        }

        return $this->tenantContext->runForTenant($tenant, function () use ($document, $notification, $jobId, $status): bool {
            // Idempotent: never overwrite a finished extraction.
            if ($document->processing_status === DocumentProcessingStatus::Completed) {
                return true;
            }

            if ($status === 'SUCCEEDED') {
                $extractedJson = $this->fetchExtractedJson($jobId);

                $document->forceFill([
                    'processing_status' => DocumentProcessingStatus::Completed,
                    'extracted_text' => $extractedJson,
                    'processing_error' => null,
                    'processing_finished_at' => now(),
                ])->save();

                $this->logAuditActivity->handle(
                    AuditEvent::DocumentProcessingCompleted,
                    $document,
                    [
                        'original_filename' => $document->original_filename,
                        'extracted_length' => mb_strlen($extractedJson),
                        'textract_job_id' => $jobId,
                    ],
                    includeRequestContext: false,
                );

                return true;
            }

            if ($status === 'FAILED') {
                $message = $notification['StatusMessage'] ?? 'Textract job failed.';
                $error = is_string($message) && $message !== ''
                    ? mb_substr($message, 0, 500)
                    : 'Textract job failed.';

                $document->forceFill([
                    'processing_status' => DocumentProcessingStatus::Failed,
                    'processing_error' => $error,
                    'processing_finished_at' => now(),
                ])->save();

                $this->logAuditActivity->handle(
                    AuditEvent::DocumentProcessingFailed,
                    $document,
                    [
                        'original_filename' => $document->original_filename,
                        'error' => $error,
                        'textract_job_id' => $jobId,
                    ],
                    includeRequestContext: false,
                );

                return true;
            }

            // IN_PROGRESS / PARTIAL_SUCCESS — leave message for a later poll if needed.
            return false;
        });
    }

    private function fetchExtractedJson(string $jobId): string
    {
        $blocks = [];
        $nextToken = null;

        do {
            $params = ['JobId' => $jobId];

            // Pagination token is only set after the first page.
            if ($nextToken !== null) {
                $params['NextToken'] = $nextToken;
            }

            $result = $this->textract->getDocumentAnalysis($params);
            $jobStatus = $result->get('JobStatus');

            if ($jobStatus === 'FAILED') {
                $statusMessage = $result->get('StatusMessage');

                throw new RuntimeException(
                    is_string($statusMessage) && $statusMessage !== ''
                        ? $statusMessage
                        : 'Textract GetDocumentAnalysis failed.',
                );
            }

            $pageBlocks = $result->get('Blocks');

            if (is_array($pageBlocks)) {
                foreach ($pageBlocks as $block) {
                    if (is_array($block)) {
                        /** @var array<string, mixed> $block */
                        $blocks[] = $block;
                    }
                }
            }

            // Normalize AWS NextToken to null when missing/empty so the loop type stays ?string.
            $token = $result->get('NextToken');
            $nextToken = is_string($token) && $token !== '' ? $token : null;
        } while ($nextToken !== null);

        try {
            return json_encode(
                $this->normalizer->normalize($blocks),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT,
            );
        } catch (JsonException $exception) {
            throw new RuntimeException('Failed to encode Textract analysis JSON.', 0, $exception);
        }
    }
}
