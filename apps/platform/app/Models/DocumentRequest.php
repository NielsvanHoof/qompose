<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTenant;
use App\Enums\DocumentRequestStatus;
use App\Enums\QuestionnaireItemType;
use Carbon\CarbonInterface;
use Database\Factories\DocumentRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Scout\Searchable;

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
 * @property CarbonInterface|null $answered_at
 * @property int|null $reviewed_by
 * @property CarbonInterface|null $reviewed_at
 * @property string|null $rejection_reason
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
    'reviewed_by',
    'reviewed_at',
    'rejection_reason',
    'status',
    'sort_order',
])]
final class DocumentRequest extends Model
{
    /** @use HasFactory<DocumentRequestFactory> */
    use BelongsToTenant, HasFactory, Searchable;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => 'file',
        'status' => 'pending',
        'sort_order' => 0,
    ];

    /**
     * Columns Scout's database engine searches with LIKE.
     *
     * @return array{id: int, title: string, instructions: string}
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'instructions' => $this->instructions ?? '',
        ];
    }

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
            'type' => QuestionnaireItemType::class,
            'status' => DocumentRequestStatus::class,
            'answer_boolean' => 'boolean',
            'answered_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }
}
