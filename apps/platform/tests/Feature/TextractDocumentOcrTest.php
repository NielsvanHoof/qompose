<?php

declare(strict_types=1);

use App\Actions\Audit\LogAuditActivityAction;
use App\Enums\DocumentProcessingStatus;
use App\Jobs\ProcessUploadedDocumentJob;
use App\Models\Client;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Models\UploadedDocument;
use App\Services\Ocr\OcrOrchestrator;
use Aws\Result;
use Aws\Textract\TextractClient;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'ocr.driver' => 'textract',
        'ocr.textract.bucket' => 'test-documents',
        'ocr.textract.sns_topic_arn' => 'arn:aws:sns:eu-west-1:123:textract',
        'ocr.textract.sns_role_arn' => 'arn:aws:iam::123:role/textract-sns',
        'ocr.textract.region' => 'eu-west-1',
    ]);
});

test('textract start persists job id and leaves document processing', function () {
    $uploaded = createUploadedDocumentForTextractTest();

    $textract = Mockery::mock(TextractClient::class);
    $textract->shouldReceive('startDocumentTextDetection')
        ->once()
        ->with(Mockery::on(function (array $args) use ($uploaded): bool {
            return ($args['DocumentLocation']['S3Object']['Bucket'] ?? null) === 'test-documents'
                && ($args['DocumentLocation']['S3Object']['Name'] ?? null) === $uploaded->path
                && ! array_key_exists('FeatureTypes', $args)
                && ($args['NotificationChannel']['SNSTopicArn'] ?? null) === 'arn:aws:sns:eu-west-1:123:textract'
                && ($args['NotificationChannel']['RoleArn'] ?? null) === 'arn:aws:iam::123:role/textract-sns'
                && ($args['JobTag'] ?? null) === (string) $uploaded->id;
        }))
        ->andReturn(new Result(['JobId' => 'textract-job-abc']));

    $this->app->instance(TextractClient::class, $textract);

    (new ProcessUploadedDocumentJob($uploaded->id))->handle(
        app(OcrOrchestrator::class),
        app(LogAuditActivityAction::class),
    );

    $uploaded->refresh();

    expect($uploaded->processing_status)->toBe(DocumentProcessingStatus::Processing)
        ->and($uploaded->textract_job_id)->toBe('textract-job-abc')
        ->and($uploaded->extracted_text)->toBeNull()
        ->and($uploaded->processing_finished_at)->toBeNull();
});

/**
 * @param  array<string, mixed>  $overrides
 */
function createUploadedDocumentForTextractTest(array $overrides = []): UploadedDocument
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

    return UploadedDocument::factory()->create([
        'tenant_id' => $tenant->id,
        'document_request_id' => $documentRequest->id,
        'disk' => 's3',
        'path' => 'tenants/'.$tenant->id.'/dossiers/'.$dossier->id.'/payslip.pdf',
        'original_filename' => 'payslip.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 4096,
        'processing_status' => DocumentProcessingStatus::Pending,
        ...$overrides,
    ]);
}
