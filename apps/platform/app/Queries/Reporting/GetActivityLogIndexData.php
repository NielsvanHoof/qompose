<?php

declare(strict_types=1);

namespace App\Queries\Reporting;

use App\Enums\AuditEvent;
use App\Models\Activity;
use App\Models\ClientAccessGrant;
use App\Models\Dossier;
use App\Models\Tenant;
use App\Models\User;
use App\Queries\Filters\ScoutSearchFilter;
use App\Queries\PaginatedIndexQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

use function array_key_exists;
use function is_array;
use function is_string;

/**
 * @extends PaginatedIndexQuery<Activity>
 */
final class GetActivityLogIndexData extends PaginatedIndexQuery
{
    private Tenant $tenant;

    /**
     * @return LengthAwarePaginator<int, array{
     *     id: int,
     *     event: string|null,
     *     label: string,
     *     description: string,
     *     causer_name: string|null,
     *     subject: array{type: string, id: int, name: string|null}|null,
     *     created_at: string|null,
     *     properties: array{ip: string|null, route: string|null},
     *     attribute_changes: array{attributes: array<string, mixed>, old: array<string, mixed>}|null
     * }>
     */
    public function handle(Tenant $tenant): LengthAwarePaginator
    {
        $this->tenant = $tenant;

        /** @var LengthAwarePaginator<int, array{id: int, event: string|null, label: string, description: string, causer_name: string|null, subject: array{type: string, id: int, name: string|null}|null, created_at: string|null, properties: array{ip: string|null, route: string|null}, attribute_changes: array{attributes: array<string, mixed>, old: array<string, mixed>}|null}> */
        return $this->paginate();
    }

    /**
     * @return Builder<Activity>
     */
    protected function subject(): Builder
    {
        return Activity::query()
            ->where('tenant_id', $this->tenant->id)
            ->with([
                'causer',
                'subject' => function ($morphTo): void {
                    if (! $morphTo instanceof MorphTo) {
                        return;
                    }

                    $morphTo->morphWith([
                        ClientAccessGrant::class => ['dossier:id,title'],
                    ]);
                },
            ]);
    }

    protected function allowedFilters(): array
    {
        return [
            AllowedFilter::exact('event'),
            ScoutSearchFilter::make(Activity::class, 'description'),
        ];
    }

    protected function allowedSorts(): array
    {
        return [
            AllowedSort::field('created_at'),
            AllowedSort::field('event'),
        ];
    }

    protected function defaultSort(): string
    {
        return '-created_at';
    }

    /**
     * @return array{
     *     id: int,
     *     event: string|null,
     *     label: string,
     *     description: string,
     *     causer_name: string|null,
     *     subject: array{type: string, id: int, name: string|null}|null,
     *     created_at: string|null,
     *     properties: array{ip: string|null, route: string|null},
     *     attribute_changes: array{attributes: array<string, mixed>, old: array<string, mixed>}|null
     * }
     */
    protected function mapModel(Model $model): array
    {
        /** @var Activity $model */
        $event = is_string($model->event) ? $model->event : null;
        $auditEvent = $event !== null ? AuditEvent::tryFrom($event) : null;

        return [
            'id' => $model->id,
            'event' => $event,
            'label' => $auditEvent?->label() ?? $model->description,
            'description' => $model->description,
            'causer_name' => $this->resolveCauserName($model),
            'subject' => $this->resolveSubject($model),
            'created_at' => $model->created_at?->toIso8601String(),
            'properties' => $this->displayProperties($model),
            'attribute_changes' => $this->displayAttributeChanges($model),
        ];
    }

    private function resolveCauserName(Activity $activity): ?string
    {
        $causer = $activity->causer;

        if ($causer instanceof User) {
            return $causer->name;
        }

        return null;
    }

    /**
     * @return array{type: string, id: int, name: string|null}|null
     */
    private function resolveSubject(Activity $activity): ?array
    {
        if ($activity->subject_type === null || $activity->subject_id === null) {
            return null;
        }

        $subject = $activity->subject;
        $type = class_basename($activity->subject_type);
        $name = null;

        if ($subject instanceof ClientAccessGrant) {
            $name = $subject->dossier?->title;
        } elseif ($subject instanceof Dossier) {
            $name = $subject->title;
        } elseif ($subject instanceof Model) {
            $name = $this->resolveSubjectName($subject);
        }

        return [
            'type' => $type,
            'id' => $activity->subject_id,
            'name' => $name,
        ];
    }

    private function resolveSubjectName(Model $subject): ?string
    {
        $attributes = $subject->getAttributes();

        if (array_key_exists('title', $attributes) && is_string($attributes['title'])) {
            return $attributes['title'];
        }

        if (array_key_exists('name', $attributes) && is_string($attributes['name'])) {
            return $attributes['name'];
        }

        if (array_key_exists('original_filename', $attributes) && is_string($attributes['original_filename'])) {
            return $attributes['original_filename'];
        }

        return null;
    }

    /**
     * Compact, safe properties for the list UI (skip bulky user_agent).
     *
     * @return array{ip: string|null, route: string|null}
     */
    private function displayProperties(Activity $activity): array
    {
        $ip = $activity->getProperty('ip');
        $route = $activity->getProperty('route');

        return [
            'ip' => is_string($ip) ? $ip : null,
            'route' => is_string($route) ? $route : null,
        ];
    }

    /**
     * @return array{attributes: array<string, mixed>, old: array<string, mixed>}|null
     */
    private function displayAttributeChanges(Activity $activity): ?array
    {
        $changes = $activity->attribute_changes;

        if ($changes === null || $changes->isEmpty()) {
            return null;
        }

        $attributes = $changes->get('attributes', []);
        $old = $changes->get('old', []);

        if (! is_array($attributes) && ! $attributes instanceof Collection) {
            $attributes = [];
        }

        if (! is_array($old) && ! $old instanceof Collection) {
            $old = [];
        }

        $attributesArray = $attributes instanceof Collection ? $attributes->all() : $attributes;
        $oldArray = $old instanceof Collection ? $old->all() : $old;

        if ($attributesArray === [] && $oldArray === []) {
            return null;
        }

        return [
            'attributes' => $attributesArray,
            'old' => $oldArray,
        ];
    }
}
