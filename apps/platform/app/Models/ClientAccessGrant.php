<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\ClientAccessGrantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $dossier_id
 * @property string $token
 * @property Carbon $expires_at
 * @property Carbon|null $revoked_at
 * @property Carbon|null $last_used_at
 * @property int $created_by
 */
#[Fillable(['tenant_id', 'dossier_id', 'token', 'expires_at', 'revoked_at', 'last_used_at', 'created_by'])]
#[Hidden(['token'])]
final class ClientAccessGrant extends Model
{
    /** @use HasFactory<ClientAccessGrantFactory> */
    use BelongsToTenant, HasFactory;

    public static function hashToken(string $plainTextToken): string
    {
        return hash('sha256', $plainTextToken);
    }

    /**
     * @return BelongsTo<Dossier, $this>
     */
    public function dossier(): BelongsTo
    {
        return $this->belongsTo(Dossier::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired();
    }

    /**
     * @param  Builder<ClientAccessGrant>  $query
     * @return Builder<ClientAccessGrant>
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query
            ->where('revoked_at', null)
            ->where('expires_at', '>', now());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }
}
