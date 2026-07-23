<?php

declare(strict_types=1);

namespace App\Services\Ocr\Textract;

use Aws\Textract\TextractClient;
use Illuminate\Support\Facades\Log;
use RuntimeException;

use function count;
use function is_array;
use function is_string;

/**
 * Paginates GetDocumentAnalysis for a finished Textract AnalyzeDocument job.
 */
final class TextractJobBlockFetcher
{
    public function __construct(
        private readonly TextractClient $textract,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function fetch(string $jobId): array
    {
        $blocks = [];
        $nextToken = null;
        $pageCount = 0;

        Log::info('OCR: fetching Textract GetDocumentAnalysis pages.', [
            'textract_job_id' => $jobId,
        ]);

        do {
            $params = ['JobId' => $jobId];

            if ($nextToken !== null) {
                $params['NextToken'] = $nextToken;
            }

            $result = $this->textract->getDocumentAnalysis($params);
            $jobStatus = $result->get('JobStatus');
            $pageCount++;

            if ($jobStatus === 'FAILED') {
                $statusMessage = $result->get('StatusMessage');
                $error = is_string($statusMessage) && $statusMessage !== ''
                    ? $statusMessage
                    : 'Textract GetDocumentAnalysis failed.';

                Log::error('OCR: GetDocumentAnalysis returned FAILED.', [
                    'textract_job_id' => $jobId,
                    'error' => $error,
                ]);

                throw new RuntimeException($error);
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

            $token = $result->get('NextToken');
            $nextToken = is_string($token) && $token !== '' ? $token : null;
        } while ($nextToken !== null);

        Log::info('OCR: fetched Textract analysis blocks.', [
            'textract_job_id' => $jobId,
            'block_count' => count($blocks),
            'api_page_count' => $pageCount,
            'job_status' => is_string($jobStatus) ? $jobStatus : null,
        ]);

        return $blocks;
    }
}
