import EmptyState from '@/components/empty-state';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import ArchiveClientButton from '@/features/clients/archive-client-button';
import type { ClientSummary } from '@/features/clients/types';
import { useTranslation } from '@/hooks/use-translation';
import type { Paginated } from '@/types/pagination';

/**
 * Client list for the clients index page.
 */
export default function ClientsListCard({
    clients,
    canManage = false,
}: {
    clients: Paginated<ClientSummary>;
    canManage?: boolean;
}) {
    const { t } = useTranslation();
    const rows = clients.data;
    const total = clients.total;

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('All clients')}</CardTitle>
                <CardDescription>
                    {total === 1
                        ? t('1 client')
                        : t(':count clients', { count: total })}
                </CardDescription>
            </CardHeader>
            <CardContent>
                {rows.length === 0 ? (
                    <EmptyState
                        title={t('Add your first client to create a dossier.')}
                    />
                ) : (
                    <div className="divide-y rounded-md border">
                        {rows.map((client) => (
                            <div
                                key={client.id}
                                className="flex flex-wrap items-center justify-between gap-3 px-4 py-3"
                            >
                                <div>
                                    <p className="font-medium">{client.name}</p>
                                    <p className="text-sm text-muted-foreground">
                                        {client.email}
                                    </p>
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    {client.dossiers_count}{' '}
                                    {client.dossiers_count === 1
                                        ? t('dossier')
                                        : t('dossiers')}
                                </p>
                                {canManage && (
                                    <ArchiveClientButton
                                        clientId={client.id}
                                        clientName={client.name}
                                        dossiersCount={client.dossiers_count}
                                    />
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
