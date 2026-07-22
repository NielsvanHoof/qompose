import { Link } from '@inertiajs/react';
import { ArrowUpRight, CircleAlert, Clock3, ScanSearch } from 'lucide-react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { WorkspaceDashboardMetrics } from '@/features/dashboard/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import { index as dossierIndex } from '@/routes/workspaces/dossiers';

/**
 * Clickable operational queues for the client follow-up workflow.
 */
export default function ReviewQueueCard({
    metrics,
}: {
    metrics: Pick<
        WorkspaceDashboardMetrics,
        'needs_review' | 'awaiting_client' | 'overdue'
    >;
}) {
    const { t } = useTranslation();
    const currentWorkspace = useCurrentWorkspace();
    const queues = [
        {
            key: 'needs_review',
            label: t('Needs review'),
            description: t('Submitted items ready for staff review'),
            count: metrics.needs_review,
            icon: ScanSearch,
            tone: 'text-primary bg-primary/10',
        },
        {
            key: 'awaiting_client',
            label: t('Awaiting client'),
            description: t('Dossiers with information still outstanding'),
            count: metrics.awaiting_client,
            icon: Clock3,
            tone: 'text-amber-700 bg-amber-500/10 dark:text-amber-300',
        },
        {
            key: 'overdue',
            label: t('Overdue'),
            description: t('Client work that has passed its due date'),
            count: metrics.overdue,
            icon: CircleAlert,
            tone: 'text-destructive bg-destructive/10',
        },
    ] as const;

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('Workflow queues')}</CardTitle>
                <CardDescription>
                    {t('Open the exact work that needs attention.')}
                </CardDescription>
            </CardHeader>
            <CardContent className="grid gap-2">
                {queues.map((queue) => (
                    <Link
                        key={queue.key}
                        href={dossierIndex(currentWorkspace, {
                            query: { filter: { queue: queue.key } },
                        })}
                        className="group flex items-center gap-3 rounded-lg border p-3 transition-colors hover:bg-muted/50 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <span
                            className={`flex size-9 shrink-0 items-center justify-center rounded-md ${queue.tone}`}
                        >
                            <queue.icon className="size-4" aria-hidden="true" />
                        </span>
                        <span className="min-w-0 flex-1">
                            <span className="flex items-baseline justify-between gap-3">
                                <span className="font-medium">
                                    {queue.label}
                                </span>
                                <span className="text-xl font-semibold tabular-nums">
                                    {queue.count}
                                </span>
                            </span>
                            <span className="block text-xs text-muted-foreground">
                                {queue.description}
                            </span>
                        </span>
                        <ArrowUpRight
                            className="size-4 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                            aria-hidden="true"
                        />
                    </Link>
                ))}
            </CardContent>
        </Card>
    );
}
