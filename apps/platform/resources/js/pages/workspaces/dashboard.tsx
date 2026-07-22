import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import DashboardMetricsGrid from '@/features/dashboard/dashboard-metrics-grid';
import RecentDossiersCard from '@/features/dashboard/recent-dossiers-card';
import ReviewQueueCard from '@/features/dashboard/review-queue-card';
import type { WorkspaceDashboardMetrics } from '@/features/dashboard/types';
import type { DossierSummary } from '@/features/dossiers/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import { dashboard as workspaceDashboard } from '@/routes/workspaces';
import { create as createClient } from '@/routes/workspaces/clients';
import { create as createDossier } from '@/routes/workspaces/dossiers';

/**
 * Workspace dashboard — metrics, recent dossiers, and review queue.
 */
export default function WorkspaceDashboard({
    metrics,
    recent_dossiers: recentDossiers,
}: {
    metrics: WorkspaceDashboardMetrics;
    recent_dossiers: DossierSummary[];
}) {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();

    setLayoutProps({
        breadcrumbs: [
            {
                title: t('Dashboard'),
                href: workspaceDashboard(currentWorkspace),
            },
        ],
    });

    return (
        <>
            <Head title={t('Dashboard')} />

            <div className="mx-auto flex w-full max-w-6xl flex-col gap-6 p-4 md:p-8">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p className="text-sm font-medium text-muted-foreground">
                            {currentWorkspace.name}
                        </p>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {t('Dossier overview')}
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {t(
                                'Keep track of client requests and review work.',
                            )}
                        </p>
                    </div>

                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={createClient(currentWorkspace)}>
                                {t('New client')}
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={createDossier(currentWorkspace)}>
                                {t('New dossier')}
                            </Link>
                        </Button>
                    </div>
                </div>

                <DashboardMetricsGrid metrics={metrics} />

                <div className="grid gap-4 lg:grid-cols-[1.5fr_1fr]">
                    <RecentDossiersCard dossiers={recentDossiers} />
                    <ReviewQueueCard metrics={metrics} />
                </div>
            </div>
        </>
    );
}
