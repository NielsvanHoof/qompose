<?php

declare(strict_types=1);

use App\Enums\DocumentProcessingStatus;
use App\Models\Client;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Models\UploadedDocument;
use Aws\Result;
use Aws\Sqs\SqsClient;
use Aws\Textract\TextractClient;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('textract consume command applies sns envelope and deletes the message', function () {
    $tenant = Tenant::factory()->create();
    $tenant->makeCurrent();

    $client = Client::factory()->create(['tenant_id' => $tenant->id]);
    $dossier = Dossier::factory()->create([
        'tenant_id' => $tenant->id,
        'client_id' => $client->id,
    ]);
    $documentRequest = DocumentRequest::factory()->create([
        'tenant_id' => $tenant->id,
        'dossier_id' => $dossier->id,
    ]);

    $uploaded = UploadedDocument::factory()->processing()->create([
        'tenant_id' => $tenant->id,
        'document_request_id' => $documentRequest->id,
        'textract_job_id' => 'job-from-sqs',
    ]);

    config([
        'ocr.textract.results_queue_url' => 'https://sqs.eu-west-1.amazonaws.com/123/textract-results',
        'ocr.textract.sqs_wait_time_seconds' => 0,
        'ocr.textract.sqs_max_messages' => 1,
    ]);

    $snsBody = json_encode([
        'Type' => 'Notification',
        'Message' => json_encode([
            'JobId' => 'job-from-sqs',
            'Status' => 'SUCCEEDED',
            'API' => 'StartDocumentAnalysis',
        ], JSON_THROW_ON_ERROR),
    ], JSON_THROW_ON_ERROR);

    $sqs = Mockery::mock(SqsClient::class);
    $sqs->shouldReceive('receiveMessage')
        ->once()
        ->andReturn(new Result([
            'Messages' => [
                [
                    'Body' => $snsBody,
                    'ReceiptHandle' => 'receipt-1',
                ],
            ],
        ]));
    $sqs->shouldReceive('deleteMessage')
        ->once()
        ->with([
            'QueueUrl' => 'https://sqs.eu-west-1.amazonaws.com/123/textract-results',
            'ReceiptHandle' => 'receipt-1',
        ])
        ->andReturn(new Result([]));

    $textract = Mockery::mock(TextractClient::class);
    $textract->shouldReceive('getDocumentAnalysis')
        ->once()
        ->with(['JobId' => 'job-from-sqs'])
        ->andReturn(new Result([
            'JobStatus' => 'SUCCEEDED',
            'Blocks' => [
                [
                    'Id' => 'key-1',
                    'BlockType' => 'KEY_VALUE_SET',
                    'EntityTypes' => ['KEY'],
                    'Relationships' => [
                        ['Type' => 'CHILD', 'Ids' => ['word-k']],
                        ['Type' => 'VALUE', 'Ids' => ['value-1']],
                    ],
                ],
                [
                    'Id' => 'value-1',
                    'BlockType' => 'KEY_VALUE_SET',
                    'EntityTypes' => ['VALUE'],
                    'Relationships' => [
                        ['Type' => 'CHILD', 'Ids' => ['word-v']],
                    ],
                ],
                ['Id' => 'word-k', 'BlockType' => 'WORD', 'Text' => 'From'],
                ['Id' => 'word-v', 'BlockType' => 'WORD', 'Text' => 'SQS'],
            ],
        ]));

    $this->app->instance(SqsClient::class, $sqs);
    $this->app->instance(TextractClient::class, $textract);

    $this->artisan('textract:consume', ['--once' => true])
        ->assertSuccessful();

    $uploaded->refresh();

    /** @var array{key_values: array<string, string>} $payload */
    $payload = json_decode((string) $uploaded->extracted_text, true, 512, JSON_THROW_ON_ERROR);

    expect($uploaded->processing_status)->toBe(DocumentProcessingStatus::Completed)
        ->and($payload['key_values']['From'])->toBe('SQS');
});
