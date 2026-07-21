<?php

declare(strict_types=1);

use App\Actions\Ocr\CompleteTextractExtractionAction;
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

uses(RefreshDatabase::class);

test('complete textract extraction stores forms and tables json on success', function () {
    $uploaded = createProcessingDocumentWithTextractJob('job-success-1');

    $textract = Mockery::mock(TextractClient::class);
    $textract->shouldReceive('getDocumentAnalysis')
        ->once()
        ->with(['JobId' => 'job-success-1'])
        ->andReturn(new Result([
            'JobStatus' => 'SUCCEEDED',
            'Blocks' => sampleAnalyzeDocumentBlocks(),
        ]));

    $this->app->instance(TextractClient::class, $textract);

    $applied = app(CompleteTextractExtractionAction::class)->handle([
        'JobId' => 'job-success-1',
        'Status' => 'SUCCEEDED',
        'API' => 'StartDocumentAnalysis',
    ]);

    expect($applied)->toBeTrue();

    $uploaded->refresh();

    /** @var array{key_values: array<string, string>, tables: list<list<list<string>>>} $payload */
    $payload = json_decode((string) $uploaded->extracted_text, true, 512, JSON_THROW_ON_ERROR);

    expect($uploaded->processing_status)->toBe(DocumentProcessingStatus::Completed)
        ->and($payload['key_values']['BSN'])->toBe('287505030')
        ->and($payload['tables'][0][0])->toBe(['Code', 'Omschrijving'])
        ->and($payload['tables'][0][1])->toBe(['1000', 'Salaris'])
        ->and($uploaded->processing_error)->toBeNull()
        ->and($uploaded->processing_finished_at)->not->toBeNull();

    expect(Activity::query()
        ->where('event', AuditEvent::DocumentProcessingCompleted->value)
        ->where('subject_id', $uploaded->id)
        ->exists())->toBeTrue();
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
        'extracted_text' => '{"key_values":{},"tables":[]}',
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

    expect($uploaded->extracted_text)->toBe('{"key_values":{},"tables":[]}');
});

/**
 * Minimal AnalyzeDocument block graph: one KEY/VALUE pair and a 2x2 table.
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
                ['Type' => 'CHILD', 'Ids' => ['word-bsn']],
                ['Type' => 'VALUE', 'Ids' => ['value-1']],
            ],
        ],
        [
            'Id' => 'value-1',
            'BlockType' => 'KEY_VALUE_SET',
            'EntityTypes' => ['VALUE'],
            'Relationships' => [
                ['Type' => 'CHILD', 'Ids' => ['word-bsn-value']],
            ],
        ],
        ['Id' => 'word-bsn', 'BlockType' => 'WORD', 'Text' => 'BSN'],
        ['Id' => 'word-bsn-value', 'BlockType' => 'WORD', 'Text' => '287505030'],
        [
            'Id' => 'table-1',
            'BlockType' => 'TABLE',
            'Relationships' => [
                ['Type' => 'CHILD', 'Ids' => ['cell-1-1', 'cell-1-2', 'cell-2-1', 'cell-2-2']],
            ],
        ],
        [
            'Id' => 'cell-1-1',
            'BlockType' => 'CELL',
            'RowIndex' => 1,
            'ColumnIndex' => 1,
            'Relationships' => [['Type' => 'CHILD', 'Ids' => ['word-code']]],
        ],
        [
            'Id' => 'cell-1-2',
            'BlockType' => 'CELL',
            'RowIndex' => 1,
            'ColumnIndex' => 2,
            'Relationships' => [['Type' => 'CHILD', 'Ids' => ['word-omschrijving']]],
        ],
        [
            'Id' => 'cell-2-1',
            'BlockType' => 'CELL',
            'RowIndex' => 2,
            'ColumnIndex' => 1,
            'Relationships' => [['Type' => 'CHILD', 'Ids' => ['word-1000']]],
        ],
        [
            'Id' => 'cell-2-2',
            'BlockType' => 'CELL',
            'RowIndex' => 2,
            'ColumnIndex' => 2,
            'Relationships' => [['Type' => 'CHILD', 'Ids' => ['word-salaris']]],
        ],
        ['Id' => 'word-code', 'BlockType' => 'WORD', 'Text' => 'Code'],
        ['Id' => 'word-omschrijving', 'BlockType' => 'WORD', 'Text' => 'Omschrijving'],
        ['Id' => 'word-1000', 'BlockType' => 'WORD', 'Text' => '1000'],
        ['Id' => 'word-salaris', 'BlockType' => 'WORD', 'Text' => 'Salaris'],
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
