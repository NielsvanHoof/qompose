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
 * Sidebar summary of dossiers in review and outstanding requests.
 */
export default function ReviewQueueCard({
    metrics,
}: {
    metrics: Pick<
        WorkspaceDashboardMetrics,
        | 'in_review'
        | 'submitted_document_requests'
        | 'outstanding_document_requests'
    >;
}) {
    const { t } = useTranslation();

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('Review queue')}</CardTitle>
                <CardDescription>
                    {t('Work that needs your attention now.')}
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                <div>
                    <p className="text-3xl font-semibold">
                        {metrics.in_review}
                    </p>
                    <p className="text-sm text-muted-foreground">
                        {t('dossiers currently in review')}
                    </p>
                </div>
                <div>
                    <p className="text-3xl font-semibold">
                        {metrics.submitted_document_requests}
                    </p>
                    <p className="text-sm text-muted-foreground">
                        {t('submitted items ready for review')}
                    </p>
                </div>
                <div>
                    <p className="text-3xl font-semibold">
                        {metrics.outstanding_document_requests}
                    </p>
                    <p className="text-sm text-muted-foreground">
                        {t('items still with the client')}
                    </p>
                </div>
            </CardContent>
        </Card>
    );
}
