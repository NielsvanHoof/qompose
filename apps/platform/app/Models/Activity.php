<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * @property int|null $tenant_id
 * @property-read Tenant|null $tenant
 */
final class Activity extends SpatieActivity
{
    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @param  Builder<Activity>  $query
     * @return Builder<Activity>
     */
    public function scopeForTenant(Builder $query, Tenant $tenant): Builder
    {
        return $query->where('tenant_id', $tenant->id);
    }

    protected static function booted(): void
    {
        self::updating(function (): void {
            throw new RuntimeException('Audit log records are immutable.');
        });
    }
}
