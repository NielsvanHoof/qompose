<?php

declare(strict_types=1);

namespace App\Queries\Notifications;

use App\Data\Notifications\WorkspaceNotificationItemData;
use App\Data\Notifications\WorkspaceNotificationsData;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\Portal\ClientQuestionnaireCompletedNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

/**
 * Recent workspace inbox notifications for the staff bell dropdown.
 */
final class FetchWorkspaceNotificationsForUserQuery
{
    private const int Limit = 20;

    public function handle(User $user, Tenant $tenant): WorkspaceNotificationsData
    {
        $baseQuery = DatabaseNotification::query()
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->getKey())
            ->where('type', ClientQuestionnaireCompletedNotification::class)
            // ->> returns text on pgsql; bind as string to avoid text = integer errors.
            ->where('data->tenant_id', (string) $tenant->getKey());

        $unreadQuery = clone $baseQuery;
        $unreadQuery->getQuery()->whereNull('read_at');
        $unreadCount = $unreadQuery->toBase()->count();

        $recentQuery = clone $baseQuery;
        $recentQuery->latest();
        $recentQuery->getQuery()->limit(self::Limit);

        /** @var Collection<int, DatabaseNotification> $notifications */
        $notifications = $recentQuery->get();

        /** @var list<WorkspaceNotificationItemData> $items */
        $items = [];

        foreach ($notifications as $notification) {
            $items[] = $this->mapNotification($notification);
        }

        return new WorkspaceNotificationsData(
            unreadCount: $unreadCount,
            items: $items,
        );
    }

    private function mapNotification(DatabaseNotification $notification): WorkspaceNotificationItemData
    {
        /** @var array<string, mixed> $data */
        $data = $notification->data;

        $clientName = (string) ($data['client_name'] ?? '');
        $dossierTitle = (string) ($data['dossier_title'] ?? '');

        return new WorkspaceNotificationItemData(
            id: $notification->id,
            type: (string) ($data['type'] ?? 'client_questionnaire_completed'),
            message: __(':client finished the questionnaire for “:dossier”.', [
                'client' => $clientName,
                'dossier' => $dossierTitle,
            ]),
            dossierId: (int) ($data['dossier_id'] ?? 0),
            dossierTitle: $dossierTitle,
            clientName: $clientName,
            dossierUrl: (string) ($data['dossier_url'] ?? ''),
            readAt: $notification->read_at?->toDateTimeString(),
            createdAt: $notification->created_at?->toDateTimeString() ?? '',
        );
    }
}
