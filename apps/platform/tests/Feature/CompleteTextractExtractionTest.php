<?php

declare(strict_types=1);

use App\Actions\Ocr\CompleteTextractExtractionAction;
use App\Contracts\Ocr\DescribesDocumentOverview;
use App\Enums\AuditEvent;
use App\Enums\DocumentProcessingStatus;
use App\Models\Activity;
use App\Models\Client;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Models\UploadedDocument;
use Aws\Result;
use Aws\Textract\TextractClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('complete textract extraction maps forms tables and asks bedrock for overview', function () {
    Event::fake([MessageLogged::class]);

    $uploaded = createProcessingDocumentWithTextractJob('job-success-1');

    $textract = Mockery::mock(TextractClient::class);
    $textract->shouldReceive('getDocumentAnalysis')
        ->once()
        ->with(['JobId' => 'job-success-1'])
        ->andReturn(new Result([
            'JobStatus' => 'SUCCEEDED',
            'Blocks' => sampleAnalyzeDocumentBlocks(),
        ]));

    $overview = Mockery::mock(DescribesDocumentOverview::class);
    $overview->shouldReceive('describe')
        ->once()
        ->with(Mockery::on(function (array $payload): bool {
            return ($payload['fields'][0]['label'] ?? null) === 'BSN'
                && ($payload['fields'][0]['value'] ?? null) === '287505030'
                && ($payload['fields'][0]['confidence'] ?? null) === 0.99
                && ($payload['tables'][0]['headers'][0] ?? null) === 'Code';
        }))
        ->andReturn([
            'document_type' => 'payslip',
            'summary' => 'Monthly payslip',
            'notes' => [],
        ]);

    $this->app->instance(TextractClient::class, $textract);
    $this->app->instance(DescribesDocumentOverview::class, $overview);

    $applied = app(CompleteTextractExtractionAction::class)->handle([
        'JobId' => 'job-success-1',
        'Status' => 'SUCCEEDED',
        'API' => 'StartDocumentAnalysis',
    ]);

    expect($applied)->toBeTrue();

    $uploaded->refresh();

    /** @var array{
     *     document_type: string,
     *     confidence: float|null,
     *     fields: list<array{label: string, value: string, confidence: float|null, sensitivity: string|null}>,
     *     tables: list<array{title: ?string, headers: list<string>, rows: list<list<string>}>>
     * } $payload
     */
    $payload = json_decode((string) $uploaded->extracted_text, true, 512, JSON_THROW_ON_ERROR);

    expect($uploaded->processing_status)->toBe(DocumentProcessingStatus::Completed)
        ->and($payload['document_type'])->toBe('payslip')
        ->and($payload['summary'])->toBe('Monthly payslip')
        ->and($payload['confidence'])->toBe(0.99)
        ->and($payload['fields'][0])->toBe([
            'label' => 'BSN',
            'value' => '287505030',
            'confidence' => 0.99,
            'sensitivity' => 'bsn',
        ])
        ->and($payload['tables'][0]['headers'])->toBe(['Code', 'Omschrijving'])
        ->and($payload['tables'][0]['rows'][0])->toBe(['1000', 'Salaris'])
        ->and($uploaded->processing_error)->toBeNull()
        ->and($uploaded->processing_finished_at)->not->toBeNull();

    expect(Activity::query()
        ->where('event', AuditEvent::DocumentProcessingCompleted->value)
        ->where('subject_id', $uploaded->id)
        ->exists())->toBeTrue();

    Event::assertDispatched(MessageLogged::class, fn (MessageLogged $log): bool => $log->level === 'info'
        && str_contains($log->message, 'OCR: applying Textract completion notification.')
        && ($log->context['textract_job_id'] ?? null) === 'job-success-1');

    Event::assertDispatched(MessageLogged::class, fn (MessageLogged $log): bool => $log->level === 'info'
        && str_contains($log->message, 'OCR: Textract extraction completed successfully.')
        && ($log->context['uploaded_document_id'] ?? null) === $uploaded->id);
});

test('complete textract extraction ignores detect document text notifications', function () {
    $uploaded = createProcessingDocumentWithTextractJob('job-detect-1');

    $textract = Mockery::mock(TextractClient::class);
    $textract->shouldNotReceive('getDocumentAnalysis');
    $this->app->instance(TextractClient::class, $textract);

    $applied = app(CompleteTextractExtractionAction::class)->handle([
        'JobId' => 'job-detect-1',
        'Status' => 'SUCCEEDED',
        'API' => 'StartDocumentTextDetection',
    ]);

    expect($applied)->toBeFalse();

    $uploaded->refresh();

    expect($uploaded->processing_status)->toBe(DocumentProcessingStatus::Processing)
        ->and($uploaded->extracted_text)->toBeNull();
});

test('complete textract extraction marks failed status', function () {
    $uploaded = createProcessingDocumentWithTextractJob('job-fail-1');

    $textract = Mockery::mock(TextractClient::class);
    $textract->shouldNotReceive('getDocumentAnalysis');
    $this->app->instance(TextractClient::class, $textract);

    $applied = app(CompleteTextractExtractionAction::class)->handle([
        'JobId' => 'job-fail-1',
        'Status' => 'FAILED',
        'StatusMessage' => 'Unsupported document format',
        'API' => 'StartDocumentAnalysis',
    ]);

    expect($applied)->toBeTrue();

    $uploaded->refresh();

    expect($uploaded->processing_status)->toBe(DocumentProcessingStatus::Failed)
        ->and($uploaded->processing_error)->toBe('Unsupported document format')
        ->and($uploaded->extracted_text)->toBeNull();

    expect(Activity::query()
        ->where('event', AuditEvent::DocumentProcessingFailed->value)
        ->where('subject_id', $uploaded->id)
        ->exists())->toBeTrue();
});

test('complete textract extraction is idempotent when already completed', function () {
    $uploaded = createProcessingDocumentWithTextractJob('job-done-1', [
        'processing_status' => DocumentProcessingStatus::Completed,
        'extracted_text' => '{"document_type":null,"summary":null,"fields":[],"tables":[],"notes":[],"confidence":null}',
        'processing_finished_at' => now(),
    ]);

    $textract = Mockery::mock(TextractClient::class);
    $textract->shouldNotReceive('getDocumentAnalysis');
    $this->app->instance(TextractClient::class, $textract);

    $applied = app(CompleteTextractExtractionAction::class)->handle([
        'JobId' => 'job-done-1',
        'Status' => 'SUCCEEDED',
        'API' => 'StartDocumentAnalysis',
    ]);

    expect($applied)->toBeTrue();

    $uploaded->refresh();

    expect($uploaded->extracted_text)->toBe('{"document_type":null,"summary":null,"fields":[],"tables":[],"notes":[],"confidence":null}');
});

/**
 * Minimal AnalyzeDocument FORMS + TABLES blocks for a tiny payslip fragment.
 *
 * @return list<array<string, mixed>>
 */
function sampleAnalyzeDocumentBlocks(): array
{
    return [
        [
            'Id' => 'key-1',
            'BlockType' => 'KEY_VALUE_SET',
            'EntityTypes' => ['KEY'],
            'Relationships' => [
                ['Type' => 'CHILD', 'Ids' => ['word-key']],
                ['Type' => 'VALUE', 'Ids' => ['value-1']],
            ],
        ],
        [
            'Id' => 'value-1',
            'BlockType' => 'KEY_VALUE_SET',
            'EntityTypes' => ['VALUE'],
            'Confidence' => 99.0,
            'Relationships' => [
                ['Type' => 'CHILD', 'Ids' => ['word-value']],
            ],
        ],
        ['Id' => 'word-key', 'BlockType' => 'WORD', 'Text' => 'BSN'],
        ['Id' => 'word-value', 'BlockType' => 'WORD', 'Text' => '287505030'],
        [
            'Id' => 'table-1',
            'BlockType' => 'TABLE',
            'Relationships' => [
                ['Type' => 'CHILD', 'Ids' => ['c11', 'c12', 'c21', 'c22']],
            ],
        ],
        [
            'Id' => 'c11',
            'BlockType' => 'CELL',
            'RowIndex' => 1,
            'ColumnIndex' => 1,
            'EntityTypes' => ['COLUMN_HEADER'],
            'Relationships' => [['Type' => 'CHILD', 'Ids' => ['w-code']]],
        ],
        [
            'Id' => 'c12',
            'BlockType' => 'CELL',
            'RowIndex' => 1,
            'ColumnIndex' => 2,
            'EntityTypes' => ['COLUMN_HEADER'],
            'Relationships' => [['Type' => 'CHILD', 'Ids' => ['w-oms']]],
        ],
        [
            'Id' => 'c21',
            'BlockType' => 'CELL',
            'RowIndex' => 2,
            'ColumnIndex' => 1,
            'Relationships' => [['Type' => 'CHILD', 'Ids' => ['w-1000']]],
        ],
        [
            'Id' => 'c22',
            'BlockType' => 'CELL',
            'RowIndex' => 2,
            'ColumnIndex' => 2,
            'Relationships' => [['Type' => 'CHILD', 'Ids' => ['w-salaris']]],
        ],
        ['Id' => 'w-code', 'BlockType' => 'WORD', 'Text' => 'Code'],
        ['Id' => 'w-oms', 'BlockType' => 'WORD', 'Text' => 'Omschrijving'],
        ['Id' => 'w-1000', 'BlockType' => 'WORD', 'Text' => '1000'],
        ['Id' => 'w-salaris', 'BlockType' => 'WORD', 'Text' => 'Salaris'],
    ];
}

/**
 * @param  array<string, mixed>  $overrides
 */
function createProcessingDocumentWithTextractJob(string $jobId, array $overrides = []): UploadedDocument
{
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

    return UploadedDocument::factory()->processing()->create([
        'tenant_id' => $tenant->id,
        'document_request_id' => $documentRequest->id,
        'disk' => 's3',
        'path' => 'tenants/'.$tenant->id.'/dossiers/'.$dossier->id.'/file.pdf',
        'textract_job_id' => $jobId,
        ...$overrides,
    ]);
}
