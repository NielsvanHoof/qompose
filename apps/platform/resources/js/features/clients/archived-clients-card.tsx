import EmptyState from '@/components/empty-state';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import RestoreClientButton from '@/features/clients/restore-client-button';
import type { ArchivedClientSummary } from '@/features/clients/types';
import { useTranslation } from '@/hooks/use-translation';
import { formatDateTime } from '@/lib/format-date-time';
import type { Paginated } from '@/types/pagination';

export default function ArchivedClientsCard({
    clients,
    canRestore,
}: {
    clients: Paginated<ArchivedClientSummary>;
    canRestore: boolean;
}) {
    const { t } = useTranslation();

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('Archived clients')}</CardTitle>
                <CardDescription>
                    {clients.total === 1
                        ? t('1 archived client')
                        : t(':count archived clients', {
                              count: clients.total,
                          })}
                </CardDescription>
            </CardHeader>
            <CardContent>
                {clients.data.length === 0 ? (
                    <EmptyState
                        title={t('No archived clients.')}
                        description={t(
                            'Clients you archive will remain available here during the retention period.',
                        )}
                    />
                ) : (
                    <div className="divide-y rounded-md border">
                        {clients.data.map((client) => (
                            <div
                                key={client.id}
                                className="flex flex-wrap items-center justify-between gap-4 px-4 py-3"
                            >
                                <div className="min-w-0">
                                    <p className="font-medium">{client.name}</p>
                                    <p className="truncate text-sm text-muted-foreground">
                                        {client.email}
                                    </p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {t('Archived :datetime', {
                                            datetime: formatDateTime(
                                                client.archived_at,
                                            ),
                                        })}
                                        {' · '}
                                        {client.dossiers_count === 1
                                            ? t('1 dossier retained')
                                            : t(':count dossiers retained', {
                                                  count: client.dossiers_count,
                                              })}
                                    </p>
                                </div>

                                {canRestore ? (
                                    <RestoreClientButton clientId={client.id} />
                                ) : null}
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
