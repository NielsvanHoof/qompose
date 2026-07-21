import { Head, setLayoutProps } from '@inertiajs/react';
import Heading from '@/components/heading';
import IndexPagination from '@/components/index-query/index-pagination';
import IndexQueryToolbar from '@/components/index-query/index-query-toolbar';
import ActivityLogCard from '@/features/audit/activity-log-card';
import type { ActivityLogEntry } from '@/features/audit/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import { index as activityIndex } from '@/routes/workspaces/activity';
import type { IndexQueryConfig, Paginated } from '@/types/pagination';

/**
 * Tenant activity / audit log index.
 */
export default function ActivityLogIndex({
    activities,
    indexQuery,
}: {
    activities: Paginated<ActivityLogEntry>;
    indexQuery: IndexQueryConfig;
    /** Current Spatie filter bag — consumed by useIndexQuery via usePage(). */
    filters?: Record<string, string>;
    sort?: string | null;
}) {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();

    setLayoutProps({
        breadcrumbs: [
            {
                title: t('Activity'),
                href: activityIndex(currentWorkspace),
            },
        ],
    });

    return (
        <>
            <Head title={t('Activity')} />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
                <Heading
                    level={1}
                    className="mb-0"
                    title={t('Activity')}
                    description={t('Latest audit events for this workspace.')}
                />

                <IndexQueryToolbar config={indexQuery} />
                <ActivityLogCard activities={activities} />
                <IndexPagination paginator={activities} />
            </div>
        </>
    );
}
