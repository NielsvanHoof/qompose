<?php

declare(strict_types=1);

namespace Database\Factories;

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
        ];
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
