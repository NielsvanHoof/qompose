import { Link } from '@inertiajs/react';
import EmptyState from '@/components/empty-state';
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
import { useTranslation } from '@/hooks/use-translation';
import { show as showDossier } from '@/routes/workspaces/dossiers';
import type { Paginated } from '@/types/pagination';

type Translate = (
    key: string,
    replacements?: Record<string, string | number>,
) => string;

/**
 * Structured list of recent tenant audit activity.
 */
export default function ActivityLogCard({
    activities,
}: {
    activities: Paginated<ActivityLogEntry>;
}) {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();
    const rows = activities.data;
    const total = activities.total;

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('Recent activity')}</CardTitle>
                <CardDescription>
                    {total === 0
                        ? t('No audit events yet')
                        : total === 1
                          ? t('1 event')
                          : t(':count events', {
                                count: total,
                            })}
                </CardDescription>
            </CardHeader>
            <CardContent>
                {rows.length === 0 ? (
                    <EmptyState
                        title={t(
                            'Audit events will appear here as your team works in this workspace.',
                        )}
                    />
                ) : (
                    <div className="divide-y rounded-md border">
                        {rows.map((activity) => (
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
                                    {activity.causer_name ?? t('System')}
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
                                                        t('Dossier #:id', {
                                                            id: activity.subject
                                                                .id,
                                                        })}
                                                </Link>
                                            ) : (
                                                <span>
                                                    {activity.subject.name ??
                                                        t(':type #:id', {
                                                            type: activity
                                                                .subject.type,
                                                            id: activity.subject
                                                                .id,
                                                        })}
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
                                                t,
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
    t: Translate,
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

    return t('Changed: :keys', { keys: keys.join(', ') });
}
