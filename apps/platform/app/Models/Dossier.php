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
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $client_id
 * @property string $title
 * @property string|null $reference
 * @property DossierStatus $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable(['tenant_id', 'client_id', 'title', 'reference', 'status'])]
final class Dossier extends Model
{
    /** @use HasFactory<DossierFactory> */
    use BelongsToTenant, HasFactory, LogsActivity, LogsTenantAuditableActivity;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
    ];

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

    public function markAwaitingClient(): void
    {
        if ($this->status === DossierStatus::Completed) {
            throw ValidationException::withMessages([
                'dossier' => 'A completed dossier cannot receive a new portal invitation.',
            ]);
        }

        if ($this->status !== DossierStatus::Draft) {
            return;
        }

        $this->update(['status' => DossierStatus::AwaitingClient]);
    }

    public function markInReview(): void
    {
        if ($this->status === DossierStatus::Completed) {
            throw ValidationException::withMessages([
                'dossier' => 'A completed dossier cannot return to review.',
            ]);
        }

        if ($this->status === DossierStatus::InReview) {
            return;
        }

        $this->update(['status' => DossierStatus::InReview]);
    }

    public function complete(): void
    {
        if ($this->status === DossierStatus::Completed) {
            throw ValidationException::withMessages([
                'dossier' => 'This dossier is already completed.',
            ]);
        }

        if ($this->status !== DossierStatus::InReview) {
            throw ValidationException::withMessages([
                'dossier' => 'Only a dossier in review can be completed.',
            ]);
        }

        $this->update(['status' => DossierStatus::Completed]);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DossierStatus::class,
        ];
    }
}
