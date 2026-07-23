<?php

declare(strict_types=1);

use App\Actions\Ocr\CompleteTextractExtractionAction;
use App\Contracts\Ocr\StructuresDocumentText;
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

test('complete textract extraction structures line text via bedrock on success', function () {
    $uploaded = createProcessingDocumentWithTextractJob('job-success-1');

    $textract = Mockery::mock(TextractClient::class);
    $textract->shouldReceive('getDocumentTextDetection')
        ->once()
        ->with(['JobId' => 'job-success-1'])
        ->andReturn(new Result([
            'JobStatus' => 'SUCCEEDED',
            'Blocks' => sampleDetectDocumentTextBlocks(),
        ]));

    $structures = Mockery::mock(StructuresDocumentText::class);
    $structures->shouldReceive('structure')
        ->once()
        ->with(Mockery::on(fn (string $text): bool => str_contains($text, 'BSN')
            && str_contains($text, '287505030')
            && str_contains($text, 'Code')
            && str_contains($text, 'Salaris')))
        ->andReturn([
            'document_type' => 'payslip',
            'summary' => 'Monthly payslip',
            'fields' => [
                ['label' => 'BSN', 'value' => '287505030'],
            ],
            'tables' => [
                [
                    'title' => 'Earnings',
                    'headers' => ['Code', 'Omschrijving'],
                    'rows' => [['1000', 'Salaris']],
                ],
            ],
            'notes' => [],
        ]);

    $this->app->instance(TextractClient::class, $textract);
    $this->app->instance(StructuresDocumentText::class, $structures);

    $applied = app(CompleteTextractExtractionAction::class)->handle([
        'JobId' => 'job-success-1',
        'Status' => 'SUCCEEDED',
        'API' => 'StartDocumentTextDetection',
    ]);

    expect($applied)->toBeTrue();

    $uploaded->refresh();

    /** @var array{
     *     document_type: string,
     *     fields: list<array{label: string, value: string}>,
     *     tables: list<array{title: string, headers: list<string>, rows: list<list<string>}>>
     * } $payload
     */
    $payload = json_decode((string) $uploaded->extracted_text, true, 512, JSON_THROW_ON_ERROR);

    expect($uploaded->processing_status)->toBe(DocumentProcessingStatus::Completed)
        ->and($payload['document_type'])->toBe('payslip')
        ->and($payload['fields'][0])->toBe(['label' => 'BSN', 'value' => '287505030'])
        ->and($payload['tables'][0]['headers'])->toBe(['Code', 'Omschrijving'])
        ->and($payload['tables'][0]['rows'][0])->toBe(['1000', 'Salaris'])
        ->and($uploaded->processing_error)->toBeNull()
        ->and($uploaded->processing_finished_at)->not->toBeNull();

    expect(Activity::query()
        ->where('event', AuditEvent::DocumentProcessingCompleted->value)
        ->where('subject_id', $uploaded->id)
        ->exists())->toBeTrue();
});

test('complete textract extraction ignores analyze document notifications', function () {
    $uploaded = createProcessingDocumentWithTextractJob('job-analyze-1');

    $textract = Mockery::mock(TextractClient::class);
    $textract->shouldNotReceive('getDocumentTextDetection');
    $this->app->instance(TextractClient::class, $textract);

    $applied = app(CompleteTextractExtractionAction::class)->handle([
        'JobId' => 'job-analyze-1',
        'Status' => 'SUCCEEDED',
        'API' => 'StartDocumentAnalysis',
    ]);

    expect($applied)->toBeFalse();

    $uploaded->refresh();

    expect($uploaded->processing_status)->toBe(DocumentProcessingStatus::Processing)
        ->and($uploaded->extracted_text)->toBeNull();
});

test('complete textract extraction marks failed status', function () {
    $uploaded = createProcessingDocumentWithTextractJob('job-fail-1');

    $textract = Mockery::mock(TextractClient::class);
    $textract->shouldNotReceive('getDocumentTextDetection');
    $this->app->instance(TextractClient::class, $textract);

    $applied = app(CompleteTextractExtractionAction::class)->handle([
        'JobId' => 'job-fail-1',
        'Status' => 'FAILED',
        'StatusMessage' => 'Unsupported document format',
        'API' => 'StartDocumentTextDetection',
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
        'extracted_text' => '{"document_type":null,"summary":null,"fields":[],"tables":[],"notes":[]}',
        'processing_finished_at' => now(),
    ]);

    $textract = Mockery::mock(TextractClient::class);
    $textract->shouldNotReceive('getDocumentTextDetection');
    $this->app->instance(TextractClient::class, $textract);

    $applied = app(CompleteTextractExtractionAction::class)->handle([
        'JobId' => 'job-done-1',
        'Status' => 'SUCCEEDED',
        'API' => 'StartDocumentTextDetection',
    ]);

    expect($applied)->toBeTrue();

    $uploaded->refresh();

    expect($uploaded->extracted_text)->toBe('{"document_type":null,"summary":null,"fields":[],"tables":[],"notes":[]}');
});

/**
 * Minimal DetectDocumentText LINE blocks for a tiny payslip fragment.
 *
 * @return list<array<string, mixed>>
 */
function sampleDetectDocumentTextBlocks(): array
{
    return [
        ['Id' => 'line-1', 'BlockType' => 'LINE', 'Page' => 1, 'Text' => 'BSN 287505030'],
        ['Id' => 'line-2', 'BlockType' => 'LINE', 'Page' => 1, 'Text' => 'Code Omschrijving'],
        ['Id' => 'line-3', 'BlockType' => 'LINE', 'Page' => 1, 'Text' => '1000 Salaris'],
        ['Id' => 'word-ignored', 'BlockType' => 'WORD', 'Page' => 1, 'Text' => 'ignored'],
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
