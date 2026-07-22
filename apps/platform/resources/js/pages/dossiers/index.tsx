import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { Archive, Plus } from 'lucide-react';
import Heading from '@/components/heading';
import IndexPagination from '@/components/index-query/index-pagination';
import IndexQueryToolbar from '@/components/index-query/index-query-toolbar';
import { Button } from '@/components/ui/button';
import DossiersListCard from '@/features/dossiers/list/dossiers-list-card';
import type { DossierSummary } from '@/features/dossiers/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import { index as clientIndex } from '@/routes/workspaces/clients';
import {
    archived as archivedDossiers,
    create,
    index as dossierIndex,
} from '@/routes/workspaces/dossiers';
import type { IndexQueryConfig, Paginated } from '@/types/pagination';

/**
 * Dossiers index — list of all firm dossiers.
 */
export default function DossierIndex({
    dossiers,
    indexQuery,
}: {
    dossiers: Paginated<DossierSummary>;
    indexQuery: IndexQueryConfig;
    /** Current Spatie filter bag — consumed by useIndexQuery via usePage(). */
    filters?: Record<string, string>;
    sort?: string | null;
}) {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();

    setLayoutProps({
        breadcrumbs: [
            {
                title: t('Dossiers'),
                href: dossierIndex(currentWorkspace),
            },
        ],
    });

    return (
        <>
            <Head title={t('Dossiers')} />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        level={1}
                        className="mb-0"
                        title={t('Dossiers')}
                        description={t(
                            'Request and review documents for each client.',
                        )}
                    />

                    <div className="flex gap-2">
                        <Button variant="ghost" asChild>
                            <Link href={archivedDossiers(currentWorkspace)}>
                                <Archive aria-hidden="true" />
                                {t('Archived')}
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={clientIndex(currentWorkspace)}>
                                {t('Clients')}
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={create(currentWorkspace)}>
                                <Plus />
                                {t('New dossier')}
                            </Link>
                        </Button>
                    </div>
                </div>

                <IndexQueryToolbar config={indexQuery} />
                <DossiersListCard dossiers={dossiers} />
                <IndexPagination paginator={dossiers} />
            </div>
        </>
    );
}
