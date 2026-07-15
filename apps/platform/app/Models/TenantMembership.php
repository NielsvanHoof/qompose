<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TenantMembershipStatus;
use Database\Factories\TenantMembershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $user_id
 * @property string|null $job_title
 * @property TenantMembershipStatus $status
 * @property Carbon|null $invited_at
 * @property Carbon|null $joined_at
 * @property Carbon|null $last_accessed_at
 */
#[Fillable(['tenant_id', 'user_id', 'job_title', 'status', 'invited_at', 'joined_at', 'last_accessed_at'])]
#[Hidden(['status', 'invited_at', 'joined_at', 'last_accessed_at'])]
final class TenantMembership extends Model
{
    /** @use HasFactory<TenantMembershipFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === TenantMembershipStatus::Active;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TenantMembershipStatus::class,
            'invited_at' => 'datetime',
            'joined_at' => 'datetime',
            'last_accessed_at' => 'datetime',
        ];
    }
}
