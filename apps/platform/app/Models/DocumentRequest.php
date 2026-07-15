<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTenant;
use App\Enums\DocumentRequestStatus;
use App\Enums\QuestionnaireItemType;
use Database\Factories\DocumentRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Questionnaire item on a dossier (file upload, text, or yes/no).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $dossier_id
 * @property QuestionnaireItemType $type
 * @property string $title
 * @property string|null $instructions
 * @property string|null $answer_text
 * @property bool|null $answer_boolean
 * @property Carbon|null $answered_at
 * @property DocumentRequestStatus $status
 * @property int $sort_order
 */
#[Fillable([
    'tenant_id',
    'dossier_id',
    'type',
    'title',
    'instructions',
    'answer_text',
    'answer_boolean',
    'answered_at',
    'status',
    'sort_order',
])]
final class DocumentRequest extends Model
{
    /** @use HasFactory<DocumentRequestFactory> */
    use BelongsToTenant, HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => 'file',
        'status' => 'pending',
        'sort_order' => 0,
    ];

    /**
     * @return BelongsTo<Dossier, $this>
     */
    public function dossier(): BelongsTo
    {
        return $this->belongsTo(Dossier::class);
    }

    /**
     * @return HasOne<UploadedDocument, $this>
     */
    public function uploadedDocument(): HasOne
    {
        return $this->hasOne(UploadedDocument::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => QuestionnaireItemType::class,
            'status' => DocumentRequestStatus::class,
            'answer_boolean' => 'boolean',
            'answered_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }
}
