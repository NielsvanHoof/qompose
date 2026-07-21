import EmptyState from '@/components/empty-state';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { ClientSummary } from '@/features/clients/types';
import { useTranslation } from '@/hooks/use-translation';

/**
 * Client list for the clients index page.
 */
export default function ClientsListCard({
    clients,
}: {
    clients: ClientSummary[];
}) {
    const { t } = useTranslation();

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('All clients')}</CardTitle>
                <CardDescription>
                    {clients.length === 1
                        ? t('1 client')
                        : t(':count clients', { count: clients.length })}
                </CardDescription>
            </CardHeader>
            <CardContent>
                {clients.length === 0 ? (
                    <EmptyState
                        title={t('Add your first client to create a dossier.')}
                    />
                ) : (
                    <div className="divide-y rounded-md border">
                        {clients.map((client) => (
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
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
