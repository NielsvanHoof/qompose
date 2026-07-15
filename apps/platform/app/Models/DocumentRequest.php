<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTenant;
use App\Enums\DocumentRequestStatus;
use Database\Factories\DocumentRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $dossier_id
 * @property string $title
 * @property string|null $instructions
 * @property DocumentRequestStatus $status
 * @property int $sort_order
 */
#[Fillable(['tenant_id', 'dossier_id', 'title', 'instructions', 'status', 'sort_order'])]
final class DocumentRequest extends Model
{
    /** @use HasFactory<DocumentRequestFactory> */
    use BelongsToTenant, HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
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
            'status' => DocumentRequestStatus::class,
            'sort_order' => 'integer',
        ];
    }
}
