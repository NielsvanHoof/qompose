<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::creating(function (Model $model): void {
            if (! isset($model->getAttributes()['tenant_id'])) {
                $tenant = Tenant::current();

                if (! $tenant instanceof Tenant) {
                    throw new RuntimeException('Cannot create tenant-owned records without an active tenant.');
                }

                $model->setAttribute('tenant_id', $tenant->getKey());
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder): void {
            $tenant = Tenant::current();

            if (! $tenant instanceof Tenant) {
                throw new RuntimeException('Cannot query tenant-owned records without an active tenant.');
            }

            $builder->where(
                $builder->qualifyColumn('tenant_id'),
                $tenant->getKey(),
            );
        });
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
