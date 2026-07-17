<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTenant;
use App\Enums\DocumentProcessingStatus;
use Database\Factories\UploadedDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $document_request_id
 * @property string $disk
 * @property string $path
 * @property string $original_filename
 * @property string $mime_type
 * @property int $size_bytes
 * @property Carbon $uploaded_at
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property string|null $rejection_reason
 * @property DocumentProcessingStatus $processing_status
 * @property string|null $extracted_text
 * @property string|null $processing_error
 * @property Carbon|null $processing_started_at
 * @property Carbon|null $processing_finished_at
 * @property string|null $textract_job_id
 */
#[Fillable([
    'tenant_id',
    'document_request_id',
    'disk',
    'path',
    'original_filename',
    'mime_type',
    'size_bytes',
    'uploaded_at',
    'reviewed_by',
    'reviewed_at',
    'rejection_reason',
    'processing_status',
    'extracted_text',
    'processing_error',
    'processing_started_at',
    'processing_finished_at',
    'textract_job_id',
])]
final class UploadedDocument extends Model
{
    /** @use HasFactory<UploadedDocumentFactory> */
    use BelongsToTenant, HasFactory;

    /**
     * @return BelongsTo<DocumentRequest, $this>
     */
    public function documentRequest(): BelongsTo
    {
        return $this->belongsTo(DocumentRequest::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'uploaded_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'processing_status' => DocumentProcessingStatus::class,
            'processing_started_at' => 'datetime',
            'processing_finished_at' => 'datetime',
        ];
    }
}
