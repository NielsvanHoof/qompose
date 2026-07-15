<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string $email
 */
#[Fillable(['tenant_id', 'name', 'email'])]
final class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use BelongsToTenant, HasFactory;

    /**
     * @return HasMany<Dossier, $this>
     */
    public function dossiers(): HasMany
    {
        return $this->hasMany(Dossier::class);
    }
}
