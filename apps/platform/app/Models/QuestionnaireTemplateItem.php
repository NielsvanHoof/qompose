<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuestionnaireItemType;
use Database\Factories\QuestionnaireTemplateItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One question/document line inside a reusable template.
 *
 * @property int $id
 * @property int $questionnaire_template_id
 * @property QuestionnaireItemType $type
 * @property string $title
 * @property string|null $instructions
 * @property int $sort_order
 */
#[Fillable([
    'questionnaire_template_id',
    'type',
    'title',
    'instructions',
    'sort_order',
])]
final class QuestionnaireTemplateItem extends Model
{
    /** @use HasFactory<QuestionnaireTemplateItemFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => 'file',
        'sort_order' => 0,
    ];

    /**
     * @return BelongsTo<QuestionnaireTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireTemplate::class, 'questionnaire_template_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => QuestionnaireItemType::class,
            'sort_order' => 'integer',
        ];
    }
}
