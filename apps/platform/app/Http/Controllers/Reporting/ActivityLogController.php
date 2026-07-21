<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reporting;

use App\Enums\AuditEvent;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Tenant;
use App\Queries\Reporting\GetActivityLogIndexData;
use Inertia\Inertia;
use Inertia\Response;

final class ActivityLogController extends Controller
{
    /**
     * Tenant-scoped audit activity feed (latest entries).
     */
    public function index(
        Tenant $tenant,
        GetActivityLogIndexData $getActivityLogIndexData,
    ): Response {
        $this->authorize('viewAny', Activity::class);

        return Inertia::render('workspaces/activity/index', [
            'activities' => $getActivityLogIndexData->handle($tenant),
            // Current Spatie query-string values for the shared IndexQuery UI.
            'filters' => request()->input('filter', []),
            'sort' => request()->query('sort'),
            // Toolbar metadata for shared IndexQuery UI (filters / sorts / defaults).
            'indexQuery' => [
                'filters' => [
                    ['key' => 'description', 'type' => 'search', 'label' => __('Search')],
                    [
                        'key' => 'event',
                        'type' => 'select',
                        'label' => __('Event'),
                        'options' => collect(AuditEvent::cases())
                            ->map(fn (AuditEvent $event): array => [
                                'value' => $event->value,
                                'label' => $event->label(),
                            ])
                            ->values()
                            ->all(),
                    ],
                ],
                'sorts' => [
                    ['key' => '-created_at', 'label' => __('Newest first')],
                    ['key' => 'created_at', 'label' => __('Oldest first')],
                    ['key' => 'event', 'label' => __('Event (A–Z)')],
                    ['key' => '-event', 'label' => __('Event (Z–A)')],
                ],
                'defaults' => [
                    'sort' => '-created_at',
                    'per_page' => 15,
                ],
            ],
        ]);
    }
}
