import { Head, setLayoutProps } from '@inertiajs/react';
import ActivityLogCard from '@/features/audit/activity-log-card';
import type { ActivityLogEntry } from '@/features/audit/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { index as activityIndex } from '@/routes/workspaces/activity';

/**
 * Tenant activity / audit log index.
 */
export default function ActivityLogIndex({
    activities,
}: {
    activities: ActivityLogEntry[];
}) {
    const currentWorkspace = useCurrentWorkspace();

    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Activity',
                href: activityIndex(currentWorkspace),
            },
        ],
    });

    return (
        <>
            <Head title="Activity" />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Activity
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Latest audit events for this workspace.
                    </p>
                </div>

                <ActivityLogCard activities={activities} />
            </div>
        </>
    );
}
