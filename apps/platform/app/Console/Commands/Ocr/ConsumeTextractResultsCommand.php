<?php

declare(strict_types=1);

namespace App\Console\Commands\Ocr;

use App\Actions\Ocr\CompleteTextractExtractionAction;
use Aws\Sqs\SqsClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use JsonException;
use Throwable;

use function is_array;
use function is_string;
use function json_decode;

/**
 * Long-polls the Textract results SQS queue (SNS envelopes) and applies OCR results.
 * Not a Laravel job queue — messages are raw SNS→SQS JSON.
 */
#[Signature('textract:consume {--once : Process one SQS batch then exit}')]
#[Description('Consume Textract SNS/SQS completion messages and store extracted text')]
final class ConsumeTextractResultsCommand extends Command
{
    public function handle(
        SqsClient $sqs,
        CompleteTextractExtractionAction $completeTextractExtraction,
        Repository $config,
    ): int {
        $queueUrl = $config->get('ocr.textract.results_queue_url');

        if (! is_string($queueUrl) || $queueUrl === '') {
            $this->error('TEXTRACT_RESULTS_QUEUE_URL is not configured.');

            return self::FAILURE;
        }

        $waitTime = max(0, min(20, (int) $config->get('ocr.textract.sqs_wait_time_seconds', 20)));
        $maxMessages = max(1, min(10, (int) $config->get('ocr.textract.sqs_max_messages', 5)));
        $once = $this->option('once');

        $this->info("Listening for Textract results on {$queueUrl}");

        do {
            $result = $sqs->receiveMessage([
                'QueueUrl' => $queueUrl,
                'MaxNumberOfMessages' => $maxMessages,
                'WaitTimeSeconds' => $waitTime,
                'VisibilityTimeout' => 60,
            ]);

            $messages = $result->get('Messages') ?? [];

            if (! is_array($messages) || $messages === []) {
                if ($once) {
                    break;
                }

                continue;
            }

            foreach ($messages as $message) {
                if (! is_array($message)) {
                    continue;
                }

                $receiptHandle = $message['ReceiptHandle'] ?? null;
                $body = $message['Body'] ?? null;

                if (! is_string($receiptHandle) || $receiptHandle === '') {
                    continue;
                }

                if (! is_string($body) || $body === '') {
                    $this->deleteMessage($sqs, $queueUrl, $receiptHandle);

                    continue;
                }

                try {
                    $notification = $this->parseSnsEnvelope($body);
                    $applied = $completeTextractExtraction->handle($notification);

                    if ($applied) {
                        $jobId = is_string($notification['JobId'] ?? null)
                            ? $notification['JobId']
                            : 'unknown';
                        $this->info("Applied Textract result for job {$jobId}");
                        $this->deleteMessage($sqs, $queueUrl, $receiptHandle);
                    } else {
                        // Leave non-final statuses visible so another poll can retry.
                        $this->warn('Skipped or deferred Textract SQS message.');
                    }
                } catch (Throwable $exception) {
                    $this->error('Failed to apply Textract message: '.$exception->getMessage());
                    // Do not delete — visibility timeout will retry / DLQ after max receives.
                }
            }
        } while (! $once);

        return self::SUCCESS;
    }

    /**
     * SNS → SQS wraps the Textract payload in a Message string.
     *
     * @return array{JobId?: mixed, Status?: mixed, StatusMessage?: mixed, API?: mixed}
     */
    private function parseSnsEnvelope(string $body): array
    {
        try {
            /** @var mixed $decoded */
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new JsonException('Invalid SQS body JSON: '.$exception->getMessage(), 0, $exception);
        }

        if (! is_array($decoded)) {
            throw new JsonException('SQS body must be a JSON object.');
        }

        $inner = $decoded['Message'] ?? $decoded;

        if (is_string($inner)) {
            try {
                /** @var mixed $innerDecoded */
                $innerDecoded = json_decode($inner, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new JsonException('Invalid SNS Message JSON: '.$exception->getMessage(), 0, $exception);
            }

            if (! is_array($innerDecoded)) {
                throw new JsonException('SNS Message must be a JSON object.');
            }

            /** @var array{JobId?: mixed, Status?: mixed, StatusMessage?: mixed, API?: mixed} $innerDecoded */
            return $innerDecoded;
        }

        if (! is_array($inner)) {
            throw new JsonException('Unexpected SNS/SQS payload shape.');
        }

        /** @var array{JobId?: mixed, Status?: mixed, StatusMessage?: mixed, API?: mixed} $inner */
        return $inner;
    }

    private function deleteMessage(SqsClient $sqs, string $queueUrl, string $receiptHandle): void
    {
        $sqs->deleteMessage([
            'QueueUrl' => $queueUrl,
            'ReceiptHandle' => $receiptHandle,
        ]);
    }
}
