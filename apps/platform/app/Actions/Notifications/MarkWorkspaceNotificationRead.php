<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\Portal\ClientQuestionnaireCompletedNotification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Mark a single workspace notification as read for the authenticated user.
 */
final class MarkWorkspaceNotificationRead
{
    public function handle(User $user, Tenant $tenant, string $notificationId): DatabaseNotification
    {
        $notification = $user->notifications()
            ->whereKey($notificationId)
            ->where('type', ClientQuestionnaireCompletedNotification::class)
            ->where('data->tenant_id', (string) $tenant->getKey())
            ->first();

        if (! $notification instanceof DatabaseNotification) {
            throw (new ModelNotFoundException)->setModel(
                DatabaseNotification::class,
                [$notificationId],
            );
        }

        $notification->markAsRead();

        return $notification;
    }
}
