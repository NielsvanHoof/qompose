import { router, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import NotificationList from '@/features/notifications/notification-list';
import { readAll as readAllNotifications } from '@/routes/workspaces/notifications';

/**
 * Top-bar bell with unread badge and inbox dropdown.
 * Only renders inside a workspace (current_firm + notifications shared props).
 */
export default function NotificationBell() {
    const { current_firm: currentFirm, notifications } = usePage().props;

    if (!currentFirm || !notifications) {
        return null;
    }

    const unreadCount = notifications.unread_count;

    const markAllRead = (): void => {
        if (unreadCount === 0) {
            return;
        }

        router.post(
            readAllNotifications.url(currentFirm),
            {},
            {
                preserveScroll: true,
                only: ['notifications'],
            },
        );
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="relative size-9"
                    aria-label={
                        unreadCount > 0
                            ? `Notifications, ${unreadCount} unread`
                            : 'Notifications'
                    }
                >
                    <Bell className="size-5" />
                    {unreadCount > 0 && (
                        <Badge
                            variant="destructive"
                            className="absolute -top-0.5 -right-0.5 h-5 min-w-5 justify-center rounded-full px-1 text-[10px] leading-none"
                        >
                            {unreadCount > 99 ? '99+' : unreadCount}
                        </Badge>
                    )}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-80 p-0">
                <div className="flex items-center justify-between gap-2 px-3 py-2">
                    <DropdownMenuLabel className="p-0">
                        Notifications
                    </DropdownMenuLabel>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="h-7 px-2 text-xs"
                        disabled={unreadCount === 0}
                        onClick={markAllRead}
                    >
                        Mark all read
                    </Button>
                </div>
                <DropdownMenuSeparator className="m-0" />
                <NotificationList
                    items={notifications.items}
                    workspace={currentFirm}
                />
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
