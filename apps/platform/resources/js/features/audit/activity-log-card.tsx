import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { ActivityLogEntry } from '@/features/audit/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { show as showDossier } from '@/routes/workspaces/dossiers';

/**
 * Structured list of recent tenant audit activity.
 */
export default function ActivityLogCard({
    activities,
}: {
    activities: ActivityLogEntry[];
}) {
    const currentWorkspace = useCurrentWorkspace();

    return (
        <Card>
            <CardHeader>
                <CardTitle>Recent activity</CardTitle>
                <CardDescription>
                    {activities.length === 0
                        ? 'No audit events yet'
                        : activities.length === 1
                          ? 'Latest 1 event'
                          : `Latest ${activities.length} events`}
                </CardDescription>
            </CardHeader>
            <CardContent>
                {activities.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Audit events will appear here as your team works in this
                        workspace.
                    </p>
                ) : (
                    <div className="divide-y rounded-md border">
                        {activities.map((activity) => (
                            <div
                                key={activity.id}
                                className="flex flex-col gap-2 px-4 py-3"
                            >
                                <div className="flex flex-wrap items-center gap-2">
                                    <Badge variant="secondary">
                                        {activity.label}
                                    </Badge>
                                    {activity.created_at && (
                                        <span className="text-xs text-muted-foreground">
                                            {new Date(
                                                activity.created_at,
                                            ).toLocaleString()}
                                        </span>
                                    )}
                                </div>

                                <p className="text-sm text-muted-foreground">
                                    {activity.causer_name ?? 'System'}
                                    {activity.subject && (
                                        <>
                                            {' · '}
                                            {activity.subject.type ===
                                            'Dossier' ? (
                                                <Link
                                                    href={showDossier({
                                                        tenant: currentWorkspace,
                                                        dossier:
                                                            activity.subject.id,
                                                    })}
                                                    className="underline-offset-4 hover:underline"
                                                >
                                                    {activity.subject.name ??
                                                        `Dossier #${activity.subject.id}`}
                                                </Link>
                                            ) : (
                                                <span>
                                                    {activity.subject.name ??
                                                        `${activity.subject.type} #${activity.subject.id}`}
                                                </span>
                                            )}
                                        </>
                                    )}
                                </p>

                                {(activity.properties.ip ||
                                    activity.properties.route ||
                                    activity.attribute_changes) && (
                                    <p className="text-xs text-muted-foreground">
                                        {[
                                            activity.properties.ip,
                                            activity.properties.route,
                                            formatAttributeChanges(
                                                activity.attribute_changes,
                                            ),
                                        ]
                                            .filter(Boolean)
                                            .join(' · ')}
                                    </p>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

/**
 * Short summary of changed attributes for model-driven audit rows.
 */
function formatAttributeChanges(
    changes: ActivityLogEntry['attribute_changes'],
): string | null {
    if (!changes) {
        return null;
    }

    const keys = [
        ...new Set([
            ...Object.keys(changes.attributes),
            ...Object.keys(changes.old),
        ]),
    ];

    if (keys.length === 0) {
        return null;
    }

    return `Changed: ${keys.join(', ')}`;
}
