<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\Portal\ClientQuestionnaireCompletedNotification;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Mark every unread workspace notification as read for the authenticated user.
 */
final class MarkAllWorkspaceNotificationsRead
{
    public function handle(User $user, Tenant $tenant): int
    {
        // Query the notifications table directly to keep Larastan happy with Eloquent builders.
        $query = DatabaseNotification::query()
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->getKey())
            ->where('type', ClientQuestionnaireCompletedNotification::class)
            ->where('data->tenant_id', (string) $tenant->getKey());

        // whereNull/update via base query avoids Larastan staticMethod.dynamicCall.
        $query->getQuery()->whereNull('read_at');

        return $query->toBase()->update(['read_at' => now()]);
    }
}
