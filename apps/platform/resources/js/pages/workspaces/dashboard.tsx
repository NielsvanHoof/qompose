import { Head, Link, usePage } from '@inertiajs/react';
import DashboardMetricsGrid from '@/components/dossiers/dashboard-metrics-grid';
import RecentDossiersCard from '@/components/dossiers/recent-dossiers-card';
import ReviewQueueCard from '@/components/dossiers/review-queue-card';
import { Button } from '@/components/ui/button';
import type { DossierSummary, WorkspaceDashboardMetrics } from '@/types';
import { dashboard } from '@/routes';
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
    const { current_firm: currentFirm } = usePage().props;

    return (
        <>
            <Head title="Dashboard" />

            <div className="mx-auto flex w-full max-w-6xl flex-col gap-6 p-4 md:p-8">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        {currentFirm && (
                            <p className="text-sm font-medium text-muted-foreground">
                                {currentFirm.name}
                            </p>
                        )}
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Dossier overview
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Keep track of client requests and review work.
                        </p>
                    </div>

                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={createClient()}>New client</Link>
                        </Button>
                        <Button asChild>
                            <Link href={createDossier()}>New dossier</Link>
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

WorkspaceDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
