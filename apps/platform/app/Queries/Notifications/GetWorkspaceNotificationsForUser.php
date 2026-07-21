<?php

declare(strict_types=1);

namespace App\Queries\Notifications;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\Portal\ClientQuestionnaireCompletedNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

/**
 * Recent workspace inbox notifications for the staff bell dropdown.
 */
final class GetWorkspaceNotificationsForUser
{
    private const int Limit = 20;

    /**
     * @return array{
     *     unread_count: int,
     *     items: list<array{
     *         id: string,
     *         type: string,
     *         message: string,
     *         dossier_id: int,
     *         dossier_title: string,
     *         client_name: string,
     *         dossier_url: string,
     *         read_at: string|null,
     *         created_at: string
     *     }>
     * }
     */
    public function handle(User $user, Tenant $tenant): array
    {
        $baseQuery = $user->notifications()
            ->where('type', ClientQuestionnaireCompletedNotification::class)
            // ->> returns text on pgsql; bind as string to avoid text = integer errors.
            ->where('data->tenant_id', (string) $tenant->getKey());

        $unreadCount = (clone $baseQuery)->whereNull('read_at')->count();

        /** @var Collection<int, DatabaseNotification> $notifications */
        $notifications = (clone $baseQuery)
            ->latest()
            ->limit(self::Limit)
            ->get();

        return [
            'unread_count' => $unreadCount,
            'items' => $notifications
                ->map(fn (DatabaseNotification $notification): array => $this->mapNotification($notification))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{
     *     id: string,
     *     type: string,
     *     message: string,
     *     dossier_id: int,
     *     dossier_title: string,
     *     client_name: string,
     *     dossier_url: string,
     *     read_at: string|null,
     *     created_at: string
     * }
     */
    private function mapNotification(DatabaseNotification $notification): array
    {
        /** @var array<string, mixed> $data */
        $data = $notification->data;

        return [
            'id' => (string) $notification->id,
            'type' => (string) ($data['type'] ?? 'client_questionnaire_completed'),
            'message' => (string) ($data['message'] ?? ''),
            'dossier_id' => (int) ($data['dossier_id'] ?? 0),
            'dossier_title' => (string) ($data['dossier_title'] ?? ''),
            'client_name' => (string) ($data['client_name'] ?? ''),
            'dossier_url' => (string) ($data['dossier_url'] ?? ''),
            'read_at' => $notification->read_at?->toDateTimeString(),
            'created_at' => $notification->created_at?->toDateTimeString() ?? '',
        ];
    }
}
