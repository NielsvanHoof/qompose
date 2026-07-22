import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { Archive } from 'lucide-react';
import Heading from '@/components/heading';
import IndexPagination from '@/components/index-query/index-pagination';
import IndexQueryToolbar from '@/components/index-query/index-query-toolbar';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import ArchivedClientsCard from '@/features/clients/archived-clients-card';
import type { ArchivedClientSummary } from '@/features/clients/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import {
    archived as archivedClients,
    index as clientIndex,
} from '@/routes/workspaces/clients';
import type { IndexQueryConfig, Paginated } from '@/types/pagination';

export default function ArchivedClients({
    clients,
    indexQuery,
    can_restore: canRestore,
}: {
    clients: Paginated<ArchivedClientSummary>;
    indexQuery: IndexQueryConfig;
    can_restore: boolean;
    filters?: Record<string, string>;
    sort?: string | null;
}) {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();

    setLayoutProps({
        breadcrumbs: [
            { title: t('Clients'), href: clientIndex(currentWorkspace) },
            {
                title: t('Archived clients'),
                href: archivedClients(currentWorkspace),
            },
        ],
    });

    return (
        <>
            <Head title={t('Archived clients')} />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        level={1}
                        className="mb-0"
                        title={t('Archived clients')}
                        description={t(
                            'Review retained client records and restore only the relationships you need.',
                        )}
                    />
                    <Button variant="outline" asChild>
                        <Link href={clientIndex(currentWorkspace)}>
                            {t('Back to clients')}
                        </Link>
                    </Button>
                </div>

                <Alert>
                    <Archive aria-hidden="true" />
                    <AlertTitle>{t('Restoring is deliberate')}</AlertTitle>
                    <AlertDescription>
                        {t(
                            'Restoring a client makes the client active again. Their archived dossiers stay archived until you restore them separately.',
                        )}
                    </AlertDescription>
                </Alert>

                <IndexQueryToolbar config={indexQuery} />
                <ArchivedClientsCard
                    clients={clients}
                    canRestore={canRestore}
                />
                <IndexPagination paginator={clients} />
            </div>
        </>
    );
}
