import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { Archive, Plus } from 'lucide-react';
import Heading from '@/components/heading';
import IndexPagination from '@/components/index-query/index-pagination';
import IndexQueryToolbar from '@/components/index-query/index-query-toolbar';
import { Button } from '@/components/ui/button';
import ClientsListCard from '@/features/clients/clients-list-card';
import type { ClientSummary } from '@/features/clients/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import {
    archived as archivedClients,
    index as clientIndex,
    create as createClient,
} from '@/routes/workspaces/clients';
import { create as createDossier } from '@/routes/workspaces/dossiers';
import type { IndexQueryConfig, Paginated } from '@/types/pagination';

/**
 * Clients index — people and organisations for document collection.
 */
export default function ClientIndex({
    clients,
    indexQuery,
    can_manage: canManage = false,
}: {
    clients: Paginated<ClientSummary>;
    indexQuery: IndexQueryConfig;
    can_manage?: boolean;
    /** Current Spatie filter bag — consumed by useIndexQuery via usePage(). */
    filters?: Record<string, string>;
    sort?: string | null;
}) {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();

    setLayoutProps({
        breadcrumbs: [
            {
                title: t('Clients'),
                href: clientIndex(currentWorkspace),
            },
        ],
    });

    return (
        <>
            <Head title={t('Clients')} />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        level={1}
                        className="mb-0"
                        title={t('Clients')}
                        description={t(
                            'Manage the people and organisations you collect documents from.',
                        )}
                    />

                    <div className="flex gap-2">
                        <Button variant="ghost" asChild>
                            <Link href={archivedClients(currentWorkspace)}>
                                <Archive aria-hidden="true" />
                                {t('Archived')}
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={createDossier(currentWorkspace)}>
                                {t('New dossier')}
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={createClient(currentWorkspace)}>
                                <Plus />
                                {t('New client')}
                            </Link>
                        </Button>
                    </div>
                </div>

                <IndexQueryToolbar config={indexQuery} />
                <ClientsListCard clients={clients} canManage={canManage} />
                <IndexPagination paginator={clients} />
            </div>
        </>
    );
}
