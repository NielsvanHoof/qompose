import EmptyState from '@/components/empty-state';
import NotificationListItem from '@/features/notifications/notification-list-item';
import type { WorkspaceNotification } from '@/features/notifications/types';
import type { Firm } from '@/features/workspaces/types';

/**
 * Scrollable notification rows for the bell dropdown.
 */
export default function NotificationList({
    items,
    workspace,
}: {
    items: WorkspaceNotification[];
    workspace: Firm;
}) {
    if (items.length === 0) {
        return (
            <EmptyState
                title="No notifications yet"
                variant="compact"
            />
        );
    }

    return (
        <div className="max-h-80 overflow-y-auto">
            {items.map((notification) => (
                <NotificationListItem
                    key={notification.id}
                    notification={notification}
                    workspace={workspace}
                />
            ))}
        </div>
    );
}
