<?php

declare(strict_types=1);

namespace App\Services\Ocr\Drivers;

use App\Contracts\Ocr\StartsDocumentOcr;
use App\Enums\DocumentProcessingStatus;
use App\Models\UploadedDocument;
use Aws\Textract\TextractClient;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

use function hash;
use function is_string;

/**
 * Starts async Textract AnalyzeDocument (FORMS + TABLES) for a PDF in S3.
 * Completion arrives later via SNS → SQS → CompleteTextractExtractionAction.
 */
final class TextractDocumentOcr implements StartsDocumentOcr
{
    public function __construct(
        private readonly TextractClient $textract,
        private readonly Repository $config,
    ) {}

    public function start(UploadedDocument $document): void
    {
        $bucket = $this->config->get('ocr.textract.bucket');
        $snsTopicArn = $this->config->get('ocr.textract.sns_topic_arn');
        $snsRoleArn = $this->config->get('ocr.textract.sns_role_arn');

        if (! is_string($bucket) || $bucket === '') {
            throw new InvalidArgumentException('OCR Textract bucket is not configured.');
        }

        if (! is_string($snsTopicArn) || $snsTopicArn === '') {
            throw new InvalidArgumentException('OCR Textract SNS topic ARN is not configured.');
        }

        if (! is_string($snsRoleArn) || $snsRoleArn === '') {
            throw new InvalidArgumentException('OCR Textract SNS role ARN is not configured.');
        }

        Log::info('OCR: starting Textract StartDocumentAnalysis.', [
            'uploaded_document_id' => $document->id,
            'tenant_id' => $document->tenant_id,
            'bucket' => $bucket,
            's3_key' => $document->path,
            'feature_types' => ['FORMS', 'TABLES'],
        ]);

        $result = $this->textract->startDocumentAnalysis([
            'ClientRequestToken' => hash('sha256', $document->id.':'.$document->path),
            'DocumentLocation' => [
                'S3Object' => [
                    'Bucket' => $bucket,
                    'Name' => $document->path,
                ],
            ],
            // FORMS = key/value pairs with confidence; TABLES = grid structure.
            'FeatureTypes' => ['FORMS', 'TABLES'],
            'NotificationChannel' => [
                'SNSTopicArn' => $snsTopicArn,
                'RoleArn' => $snsRoleArn,
            ],
            // JobTag helps humans debug; lookup uses textract_job_id.
            'JobTag' => (string) $document->id,
        ]);

        $jobId = $result->get('JobId');

        if (! is_string($jobId) || $jobId === '') {
            throw new RuntimeException('Textract did not return a JobId.');
        }

        // Only attach the external job to the exact upload revision that started it.
        $updated = UploadedDocument::query()
            ->whereKey($document->id)
            ->where('path', $document->path)
            ->where('processing_status', DocumentProcessingStatus::Processing->value)
            ->update([
                'textract_job_id' => $jobId,
                'extracted_text' => null,
                'processing_error' => null,
                'processing_finished_at' => null,
            ]);

        if ($updated !== 1) {
            Log::warning('OCR: discarded Textract job id for a stale upload revision.', [
                'uploaded_document_id' => $document->id,
                'tenant_id' => $document->tenant_id,
                'textract_job_id' => $jobId,
                'expected_path' => $document->path,
            ]);

            return;
        }

        $document->refresh();

        Log::info('OCR: Textract job started — awaiting SNS/SQS completion.', [
            'uploaded_document_id' => $document->id,
            'tenant_id' => $document->tenant_id,
            'textract_job_id' => $jobId,
        ]);
    }
}
