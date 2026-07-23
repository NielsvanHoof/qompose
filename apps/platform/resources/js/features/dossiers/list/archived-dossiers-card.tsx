import EmptyState from '@/components/empty-state';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import RestoreDossierButton from '@/features/dossiers/restore-dossier-button';
import {
    dossierStatusBadgeVariant,
    dossierStatusLabel,
} from '@/features/dossiers/status/dossier-status';
import type { ArchivedDossierSummary } from '@/features/dossiers/types';
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime } from '@/lib/format-date-time';
import type { Paginated } from '@/types/pagination';

export default function ArchivedDossiersCard({
    dossiers,
    canRestore,
    canRestoreClients,
}: {
    dossiers: Paginated<ArchivedDossierSummary>;
    canRestore: boolean;
    canRestoreClients: boolean;
}) {
    const { t, locale } = useTranslation();

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('Archived dossiers')}</CardTitle>
                <CardDescription>
                    {dossiers.total === 1
                        ? t('1 archived dossier')
                        : t(':count archived dossiers', {
                              count: dossiers.total,
                          })}
                </CardDescription>
            </CardHeader>
            <CardContent>
                {dossiers.data.length === 0 ? (
                    <EmptyState
                        title={t('No archived dossiers.')}
                        description={t(
                            'Dossiers you archive will remain available here during the retention period.',
                        )}
                    />
                ) : (
                    <div className="divide-y rounded-md border">
                        {dossiers.data.map((dossier) => (
                            <div
                                key={dossier.id}
                                className="flex flex-wrap items-center justify-between gap-4 px-4 py-3"
                            >
                                <div className="min-w-0">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <p className="font-medium">
                                            {dossier.title}
                                        </p>
                                        <Badge
                                            variant={dossierStatusBadgeVariant(
                                                dossier.status,
                                            )}
                                        >
                                            {dossierStatusLabel(
                                                dossier.status,
                                                t,
                                            )}
                                        </Badge>
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        {dossier.client_name}
                                        {dossier.reference
                                            ? ` · ${dossier.reference}`
                                            : null}
                                    </p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {t('Archived :datetime', {
                                            datetime: formatDateTime(
                                                dossier.archived_at,
                                                locale,
                                            ),
                                        })}
                                    </p>
                                </div>

                                {canRestore ? (
                                    <RestoreDossierButton
                                        dossierId={dossier.id}
                                        clientArchived={dossier.client_archived}
                                        canRestoreClient={canRestoreClients}
                                    />
                                ) : null}
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
