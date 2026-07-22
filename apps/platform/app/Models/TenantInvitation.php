<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTenant;
use App\Enums\Role;
use Database\Factories\TenantInvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Pending staff invite into a workspace. Looked up by hashed token before a
 * tenant is current (accept links), so callers must use withoutGlobalScopes.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $email
 * @property Role $role
 * @property string $token
 * @property int $invited_by
 * @property Carbon $expires_at
 * @property Carbon|null $accepted_at
 * @property Carbon|null $revoked_at
 */
#[Fillable(['tenant_id', 'email', 'role', 'token', 'invited_by', 'expires_at', 'accepted_at', 'revoked_at'])]
#[Hidden(['token'])]
final class TenantInvitation extends Model
{
    /** @use HasFactory<TenantInvitationFactory> */
    use BelongsToTenant, HasFactory, MassPrunable;

    public static function hashToken(string $plainTextToken): string
    {
        return hash('sha256', $plainTextToken);
    }

    /**
     * Apply "still pending" constraints to an invitation query.
     *
     * @param  Builder<TenantInvitation>  $query
     */
    public static function constrainToPending(Builder $query): void
    {
        $query->getQuery()
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now());
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return ! $this->isAccepted() && ! $this->isRevoked() && ! $this->isExpired();
    }

    /**
     * @return Builder<TenantInvitation>
     */
    public function prunable(): Builder
    {
        $cutoff = now()->subDays((int) config('retention.member_invitations_days'));

        return self::query()
            ->where(function (Builder $query) use ($cutoff): void {
                $query->getQuery()
                    ->whereNotNull('revoked_at')
                    ->where('revoked_at', '<=', $cutoff);
            })
            ->orWhere(function (Builder $query) use ($cutoff): void {
                $query->getQuery()
                    ->whereNotNull('accepted_at')
                    ->where('accepted_at', '<=', $cutoff);
            })
            ->orWhere(function (Builder $query) use ($cutoff): void {
                $query->getQuery()
                    ->whereNull('revoked_at')
                    ->whereNull('accepted_at')
                    ->where('expires_at', '<=', $cutoff);
            });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}
