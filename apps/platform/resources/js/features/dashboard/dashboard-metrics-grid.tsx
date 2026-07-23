import { Link } from '@inertiajs/react';
import { Check, ClipboardList, Users } from 'lucide-react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { WorkspaceDashboardMetrics } from '@/features/dashboard/types';
import { dossierStageLabel } from '@/features/dossiers/stage/dossier-stage';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import { index as clientIndex } from '@/routes/workspaces/clients';
import { index as dossierIndex } from '@/routes/workspaces/dossiers';

/**
 * Dashboard workflow spine — stage-first metrics instead of a flat card grid.
 */
export default function DashboardMetricsGrid({
    metrics,
}: {
    metrics: WorkspaceDashboardMetrics;
}) {
    const { t } = useTranslation();
    const currentWorkspace = useCurrentWorkspace();

    const stages = [
        {
            key: 'prepare' as const,
            count: metrics.open_dossiers,
            description: t('Open dossiers in progress'),
            href: dossierIndex(currentWorkspace),
            detail: t(':count outstanding requests', {
                count: metrics.outstanding_document_requests,
            }),
        },
        {
            key: 'invite' as const,
            count: metrics.awaiting_client,
            description: t('Waiting for client uploads'),
            href: dossierIndex(currentWorkspace, {
                query: { filter: { queue: 'awaiting_client' } },
            }),
            detail: t('Portal invitations and reminders'),
        },
        {
            key: 'review' as const,
            count: metrics.needs_review,
            description: t('Submitted items ready for staff'),
            href: dossierIndex(currentWorkspace, {
                query: { filter: { queue: 'needs_review' } },
            }),
            detail: t(':count in review', { count: metrics.in_review }),
        },
    ];

    // Highlight the busiest stage so the spine has a clear current focus.
    const focusKey = stages.reduce((busiest, stage) =>
        stage.count > busiest.count ? stage : busiest,
    ).key;

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 className="text-base font-semibold tracking-tight">
                        {t('Dossier workflow')}
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        {t(
                            'Follow work from prepare through invite to review.',
                        )}
                    </p>
                </div>
                <Link
                    href={clientIndex(currentWorkspace)}
                    className="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm transition-colors hover:bg-muted/50 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <Users
                        className="size-4 text-muted-foreground"
                        aria-hidden
                    />
                    <span className="text-muted-foreground">
                        {t('Clients')}
                    </span>
                    <span className="font-data text-base font-semibold tabular-nums">
                        {metrics.clients}
                    </span>
                </Link>
            </div>

            <nav aria-label={t('Dossier workflow')}>
                <ol className="flex flex-col gap-3 lg:flex-row lg:items-stretch">
                    {stages.map((stage, index) => {
                        const isFocus = stage.key === focusKey;
                        const isLast = index === stages.length - 1;

                        return (
                            <li
                                key={stage.key}
                                className="flex min-w-0 flex-1 items-stretch gap-3"
                            >
                                <Link
                                    href={stage.href}
                                    className={cn(
                                        'group flex min-w-0 flex-1 flex-col rounded-xl border bg-card p-4 shadow-sm transition-colors hover:bg-muted/40 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                                        isFocus &&
                                            'border-primary/40 ring-1 ring-primary/20',
                                    )}
                                >
                                    <div className="flex items-center gap-2">
                                        <span
                                            className={cn(
                                                'flex size-7 shrink-0 items-center justify-center rounded-full border text-xs font-medium',
                                                isFocus
                                                    ? 'border-primary bg-primary text-primary-foreground'
                                                    : stage.count === 0
                                                      ? 'border-success-border bg-success-muted text-success-foreground'
                                                      : 'border-border bg-background text-muted-foreground',
                                            )}
                                        >
                                            {stage.count === 0 && !isFocus ? (
                                                <Check
                                                    className="size-3.5"
                                                    aria-hidden
                                                />
                                            ) : (
                                                index + 1
                                            )}
                                        </span>
                                        <span className="text-sm font-medium">
                                            {dossierStageLabel(stage.key, t)}
                                        </span>
                                    </div>
                                    <p className="mt-3 font-data text-3xl font-semibold tracking-tight tabular-nums">
                                        {stage.count}
                                    </p>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {stage.description}
                                    </p>
                                    <p className="mt-3 flex items-center gap-1.5 text-xs text-muted-foreground">
                                        <ClipboardList
                                            className="size-3.5 shrink-0"
                                            aria-hidden
                                        />
                                        {stage.detail}
                                    </p>
                                </Link>

                                {!isLast && (
                                    <div
                                        aria-hidden
                                        className="hidden items-center justify-center lg:flex"
                                    >
                                        <span className="h-px w-6 bg-border" />
                                    </div>
                                )}
                            </li>
                        );
                    })}
                </ol>
            </nav>

            {/* Compact secondary counters kept off the main spine. */}
            <Card className="py-4">
                <CardHeader className="px-4 py-0">
                    <CardTitle className="text-sm font-medium">
                        {t('Also tracking')}
                    </CardTitle>
                    <CardDescription>
                        {t('Extra volume signals for the firm.')}
                    </CardDescription>
                </CardHeader>
                <CardContent className="grid gap-3 px-4 pt-3 sm:grid-cols-2">
                    <div className="rounded-lg border px-3 py-2">
                        <p className="text-xs text-muted-foreground">
                            {t('Outstanding requests')}
                        </p>
                        <p className="font-data text-xl font-semibold tabular-nums">
                            {metrics.outstanding_document_requests}
                        </p>
                    </div>
                    <div className="rounded-lg border px-3 py-2">
                        <p className="text-xs text-muted-foreground">
                            {t('Overdue')}
                        </p>
                        <p className="font-data text-xl font-semibold tabular-nums">
                            {metrics.overdue}
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
