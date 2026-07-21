<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notifications;

use App\Actions\Notifications\MarkAllWorkspaceNotificationsReadAction;
use App\Actions\Notifications\MarkWorkspaceNotificationReadAction;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class WorkspaceNotificationController extends Controller
{
    /**
     * Mark one workspace notification as read.
     */
    public function update(
        Request $request,
        Tenant $tenant,
        string $notification,
        MarkWorkspaceNotificationReadAction $markWorkspaceNotificationRead,
    ): RedirectResponse {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $markWorkspaceNotificationRead->handle($user, $tenant, $notification);

        return back();
    }

    /**
     * Mark every unread workspace notification as read for this tenant.
     */
    public function store(
        Request $request,
        Tenant $tenant,
        MarkAllWorkspaceNotificationsReadAction $markAllWorkspaceNotificationsRead,
    ): RedirectResponse {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $markAllWorkspaceNotificationsRead->handle($user, $tenant);

        return back();
    }
}
