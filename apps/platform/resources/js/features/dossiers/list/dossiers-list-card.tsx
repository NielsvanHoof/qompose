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
import {
    dossierStatusBadgeVariant,
    dossierStatusLabel,
} from '@/features/dossiers/status/dossier-status';
import type { DossierSummary } from '@/features/dossiers/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import { show } from '@/routes/workspaces/dossiers';
import type { Paginated } from '@/types/pagination';

/**
 * Full dossier list for the dossiers index page.
 */
export default function DossiersListCard({
    dossiers,
}: {
    dossiers: Paginated<DossierSummary>;
}) {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();
    const rows = dossiers.data;
    const total = dossiers.total;

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('All dossiers')}</CardTitle>
                <CardDescription>
                    {total === 1
                        ? t('1 dossier')
                        : t(':count dossiers', { count: total })}
                </CardDescription>
            </CardHeader>
            <CardContent>
                {rows.length === 0 ? (
                    <EmptyState
                        title={t(
                            'Create a dossier to start collecting documents.',
                        )}
                    />
                ) : (
                    <div className="divide-y rounded-md border">
                        {rows.map((dossier) => (
                            <Link
                                key={dossier.id}
                                href={show({
                                    tenant: currentWorkspace,
                                    dossier: dossier.id,
                                })}
                                className="flex flex-wrap items-center justify-between gap-3 px-4 py-3 transition-colors hover:bg-muted/50"
                            >
                                <div>
                                    <p className="font-medium">
                                        {dossier.title}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {dossier.client_name}
                                        {dossier.reference &&
                                            ` · ${dossier.reference}`}
                                    </p>
                                    {(dossier.due_date ||
                                        dossier.responsible_name) && (
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {dossier.due_date
                                                ? t('Due :date', {
                                                      date: new Date(
                                                          `${dossier.due_date}T00:00:00`,
                                                      ).toLocaleDateString(),
                                                  })
                                                : t('No due date')}
                                            {dossier.responsible_name
                                                ? ` · ${dossier.responsible_name}`
                                                : null}
                                        </p>
                                    )}
                                </div>
                                <Badge
                                    variant={dossierStatusBadgeVariant(
                                        dossier.status,
                                    )}
                                >
                                    {dossierStatusLabel(dossier.status, t)}
                                </Badge>
                            </Link>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
