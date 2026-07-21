import { ClipboardList, FileClock, FolderOpen, Users } from 'lucide-react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { WorkspaceDashboardMetrics } from '@/features/dashboard/types';
import { useTranslation } from '@/hooks/use-translation';

/**
 * Four metric summary cards on the workspace dashboard.
 */
export default function DashboardMetricsGrid({
    metrics,
}: {
    metrics: WorkspaceDashboardMetrics;
}) {
    const { t } = useTranslation();

    const metricCards = [
        {
            label: t('Clients'),
            value: metrics.clients,
            description: t('People and organisations you support'),
            icon: Users,
        },
        {
            label: t('Open dossiers'),
            value: metrics.open_dossiers,
            description: t('Dossiers that still need attention'),
            icon: FolderOpen,
        },
        {
            label: t('Awaiting client'),
            value: metrics.awaiting_client,
            description: t('Waiting for requested information'),
            icon: FileClock,
        },
        {
            label: t('Outstanding requests'),
            value: metrics.outstanding_document_requests,
            description: t('Documents still missing or rejected'),
            icon: ClipboardList,
        },
    ];

    return (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            {metricCards.map((metric) => (
                <Card key={metric.label}>
                    <CardHeader className="flex flex-row items-start justify-between space-y-0">
                        <div>
                            <CardDescription>{metric.label}</CardDescription>
                            <CardTitle className="mt-2 text-3xl">
                                {metric.value}
                            </CardTitle>
                        </div>
                        <metric.icon className="size-5 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <p className="text-sm text-muted-foreground">
                            {metric.description}
                        </p>
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}
