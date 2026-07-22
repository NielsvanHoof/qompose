<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToTenant;
use App\Concerns\LogsTenantAuditableActivity;
use App\Enums\DossierStatus;
use Database\Factories\DossierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $client_id
 * @property string $title
 * @property string|null $reference
 * @property DossierStatus $status
 * @property int|null $responsible_user_id
 * @property Carbon|null $due_date
 * @property int|null $reminder_interval_days
 * @property Carbon|null $next_reminder_at
 * @property Carbon|null $last_client_message_sent_at
 * @property Carbon|null $last_client_opened_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'tenant_id',
    'client_id',
    'title',
    'reference',
    'status',
    'responsible_user_id',
    'due_date',
    'reminder_interval_days',
])]
final class Dossier extends Model
{
    /** @use HasFactory<DossierFactory> */
    use BelongsToTenant, HasFactory, LogsActivity, LogsTenantAuditableActivity, Searchable, SoftDeletes;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
    ];

    /**
     * Columns Scout's database engine searches with LIKE.
     *
     * @return array{id: int, title: string, reference: string}
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'reference' => $this->reference ?? '',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName(config('activitylog.default_log_name'))
            ->logOnly($this->auditableAttributes())
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    /**
     * @return HasMany<DocumentRequest, $this>
     */
    public function documentRequests(): HasMany
    {
        return $this->hasMany(DocumentRequest::class);
    }

    /**
     * @return HasMany<ClientAccessGrant, $this>
     */
    public function clientAccessGrants(): HasMany
    {
        return $this->hasMany(ClientAccessGrant::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DossierStatus::class,
            'due_date' => 'date',
            'reminder_interval_days' => 'integer',
            'next_reminder_at' => 'datetime',
            'last_client_message_sent_at' => 'datetime',
            'last_client_opened_at' => 'datetime',
        ];
    }
}
