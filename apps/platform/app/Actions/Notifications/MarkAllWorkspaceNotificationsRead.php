<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\Portal\ClientQuestionnaireCompletedNotification;

/**
 * Mark every unread workspace notification as read for the authenticated user.
 */
final class MarkAllWorkspaceNotificationsRead
{
    public function handle(User $user, Tenant $tenant): int
    {
        return $user->unreadNotifications()
            ->where('type', ClientQuestionnaireCompletedNotification::class)
            ->where('data->tenant_id', (string) $tenant->getKey())
            ->update(['read_at' => now()]);
    }
}
