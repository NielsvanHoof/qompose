import { router } from '@inertiajs/react';
import type { WorkspaceNotification } from '@/features/notifications/types';
import type { Firm } from '@/features/workspaces/types';
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime } from '@/lib/format-date-time';
import { cn } from '@/lib/utils';
import { read as readNotification } from '@/routes/workspaces/notifications';

/**
 * Single inbox row — marks read then navigates to the dossier.
 */
export default function NotificationListItem({
    notification,
    workspace,
}: {
    notification: WorkspaceNotification;
    workspace: Firm;
}) {
    const { t, locale } = useTranslation();
    const isUnread = notification.read_at === null;

    const message = t(':client finished the questionnaire for “:dossier”.', {
        client: notification.client_name,
        dossier: notification.dossier_title,
    });

    const openNotification = (): void => {
        const visitDossier = (): void => {
            router.visit(notification.dossier_url);
        };

        if (!isUnread) {
            visitDossier();

            return;
        }

        router.post(
            readNotification.url({
                tenant: workspace,
                notification: notification.id,
            }),
            {},
            {
                preserveScroll: true,
                only: ['notifications'],
                onSuccess: visitDossier,
            },
        );
    };

    return (
        <button
            type="button"
            onClick={openNotification}
            className={cn(
                'flex w-full flex-col gap-1 px-3 py-2.5 text-left text-sm transition-colors hover:bg-accent',
                isUnread && 'bg-accent/40',
            )}
        >
            <div className="flex items-start gap-2">
                {isUnread ? (
                    <span
                        aria-hidden
                        className="mt-1.5 size-1.5 shrink-0 rounded-full bg-primary"
                    />
                ) : (
                    <span aria-hidden className="mt-1.5 size-1.5 shrink-0" />
                )}
                <span
                    className={cn(
                        'min-w-0 flex-1 leading-snug',
                        isUnread ? 'font-medium' : 'text-muted-foreground',
                    )}
                >
                    {message}
                </span>
            </div>
            {notification.created_at && (
                <span className="pl-3.5 font-data text-xs text-muted-foreground">
                    {formatDateTime(notification.created_at, locale)}
                </span>
            )}
        </button>
    );
}
