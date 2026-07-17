<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DocumentProcessingStatus;
use App\Models\DocumentRequest;
use App\Models\UploadedDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UploadedDocument>
 */
final class UploadedDocumentFactory extends Factory
{
    protected $model = UploadedDocument::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_request_id' => DocumentRequest::factory(),
            'disk' => 'local',
            'path' => 'uploads/'.fake()->uuid().'.pdf',
            'original_filename' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(10_000, 5_000_000),
            'uploaded_at' => now(),
            // New uploads start waiting for the mock OCR job.
            'processing_status' => DocumentProcessingStatus::Pending,
            'extracted_text' => null,
            'processing_error' => null,
            'processing_started_at' => null,
            'processing_finished_at' => null,
            'textract_job_id' => null,
        ];
    }

    /**
     * Mark the upload as OCR-complete with optional extracted text.
     */
    public function processed(?string $extractedText = 'Mock OCR extract'): static
    {
        return $this->state(fn (): array => [
            'processing_status' => DocumentProcessingStatus::Completed,
            'extracted_text' => $extractedText,
            'processing_error' => null,
            'processing_started_at' => now()->subMinute(),
            'processing_finished_at' => now(),
        ]);
    }

    /**
     * Mark the upload as currently being processed by OCR.
     */
    public function processing(): static
    {
        return $this->state(fn (): array => [
            'processing_status' => DocumentProcessingStatus::Processing,
            'extracted_text' => null,
            'processing_error' => null,
            'processing_started_at' => now(),
            'processing_finished_at' => null,
        ]);
    }

    /**
     * Mark the upload as failed OCR with an error message.
     */
    public function failed(string $error = 'Mock OCR failed.'): static
    {
        return $this->state(fn (): array => [
            'processing_status' => DocumentProcessingStatus::Failed,
            'extracted_text' => null,
            'processing_error' => $error,
            'processing_started_at' => now()->subMinute(),
            'processing_finished_at' => now(),
        ]);
    }

    public function configure(): static
    {
        return $this->afterMaking(function (UploadedDocument $uploadedDocument): void {
            if ($uploadedDocument->tenant_id !== null) {
                return;
            }

            $documentRequest = DocumentRequest::query()
                ->withoutGlobalScopes()
                ->find($uploadedDocument->document_request_id);

            if ($documentRequest instanceof DocumentRequest) {
                $uploadedDocument->tenant_id = $documentRequest->tenant_id;
            }
        });
    }
}
