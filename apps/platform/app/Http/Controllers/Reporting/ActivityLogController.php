<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reporting;

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
        ]);
    }
}
