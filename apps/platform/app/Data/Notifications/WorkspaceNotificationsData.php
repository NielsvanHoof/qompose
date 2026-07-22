<?php

declare(strict_types=1);

namespace App\Data\Notifications;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Shared Inertia notifications payload for the staff bell.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class WorkspaceNotificationsData implements Arrayable
{
    /**
     * @param  list<WorkspaceNotificationItemData>  $items
     */
    public function __construct(
        public int $unreadCount,
        public array $items,
    ) {}

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
    public function toArray(): array
    {
        return [
            'unread_count' => $this->unreadCount,
            'items' => array_map(
                static fn (WorkspaceNotificationItemData $item): array => $item->toArray(),
                $this->items,
            ),
        ];
    }
}
