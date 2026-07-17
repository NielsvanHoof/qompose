<?php

declare(strict_types=1);

namespace App\Services\Ocr;

use App\Contracts\Ocr\StartsDocumentOcr;
use App\Models\UploadedDocument;
use Aws\Textract\TextractClient;
use Illuminate\Contracts\Config\Repository;
use InvalidArgumentException;
use RuntimeException;

use function is_string;

/**
 * Starts async Textract AnalyzeDocument (FORMS + TABLES) for a PDF in S3.
 * Completion arrives later via SNS → SQS → CompleteTextractExtraction.
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

        $result = $this->textract->startDocumentAnalysis([
            'DocumentLocation' => [
                'S3Object' => [
                    'Bucket' => $bucket,
                    'Name' => $document->path,
                ],
            ],
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

        // Stay in processing until the SQS consumer applies the result.
        $document->forceFill([
            'textract_job_id' => $jobId,
            'extracted_text' => null,
            'processing_error' => null,
            'processing_finished_at' => null,
        ])->save();
    }
}
