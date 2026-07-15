<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Models\Concerns\ImplementsTenant;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 */
#[Fillable(['name', 'slug'])]
final class Tenant extends Model implements IsTenant
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory, ImplementsTenant, UsesMultitenancyConfig;

    /**
     * @return HasMany<TenantMembership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(TenantMembership::class);
    }

    /**
     * @return HasMany<Client, $this>
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    /**
     * @return HasMany<Dossier, $this>
     */
    public function dossiers(): HasMany
    {
        return $this->hasMany(Dossier::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
