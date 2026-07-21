<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reporting;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Tenant;
use App\Queries\Reporting\FetchActivityLogIndexQuery;
use Inertia\Inertia;
use Inertia\Response;

final class ActivityLogController extends Controller
{
    /**
     * Tenant-scoped audit activity feed (latest entries).
     */
    public function index(
        Tenant $tenant,
        FetchActivityLogIndexQuery $fetchActivityLogIndex,
    ): Response {
        $this->authorize('viewAny', Activity::class);

        return Inertia::render('workspaces/activity/index', [
            'activities' => $fetchActivityLogIndex->handle($tenant),
            ...$fetchActivityLogIndex->indexQueryProps(),
        ]);
    }
}
