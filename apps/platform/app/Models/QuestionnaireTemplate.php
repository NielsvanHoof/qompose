<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuestionnaireTemplateCategory;
use Database\Factories\QuestionnaireTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Reusable questionnaire pack — system-wide (tenant_id null) or firm-owned.
 *
 * @property int $id
 * @property int|null $tenant_id
 * @property string $name
 * @property string|null $description
 * @property QuestionnaireTemplateCategory $category
 * @property int|null $source_template_id
 */
#[Fillable([
    'tenant_id',
    'name',
    'description',
    'category',
    'source_template_id',
])]
final class QuestionnaireTemplate extends Model
{
    /** @use HasFactory<QuestionnaireTemplateFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'category' => 'custom',
    ];

    /**
     * System templates plus templates owned by the current tenant.
     *
     * Static starter avoids Larastan/strict-rules treating local scopes as
     * static methods called dynamically on the Eloquent builder.
     *
     * @return Builder<QuestionnaireTemplate>
     */
    public static function queryVisibleToCurrentTenant(): Builder
    {
        // self::query() (final class) keeps Builder<QuestionnaireTemplate> invariant.
        $query = self::query();
        $tenant = Tenant::current();

        if (! $tenant instanceof Tenant) {
            return $query->where('tenant_id', null);
        }

        return $query->where(function (Builder $builder) use ($tenant): void {
            $builder->where('tenant_id', null)
                ->orWhere('tenant_id', $tenant->getKey());
        });
    }

    public function isSystem(): bool
    {
        return $this->tenant_id === null;
    }

    /**
     * Only resolve system templates or templates owned by the current tenant.
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $field ??= $this->getRouteKeyName();

        return self::queryVisibleToCurrentTenant()
            ->where($field, $value)
            ->first();
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<QuestionnaireTemplate, $this>
     */
    public function sourceTemplate(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_template_id');
    }

    /**
     * @return HasMany<QuestionnaireTemplateItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(QuestionnaireTemplateItem::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => QuestionnaireTemplateCategory::class,
        ];
    }
}
