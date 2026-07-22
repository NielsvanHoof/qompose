import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { Archive, FolderOpen, Mail, Pencil } from 'lucide-react';
import IndexPagination from '@/components/index-query/index-pagination';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import ArchiveClientButton from '@/features/clients/archive-client-button';
import type { ClientDetails } from '@/features/clients/types';
import DossiersListCard from '@/features/dossiers/list/dossiers-list-card';
import type { DossierSummary } from '@/features/dossiers/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import {
    index as clientIndex,
    edit as editClient,
    show as showClient,
} from '@/routes/workspaces/clients';
import {
    archived as archivedDossiers,
    create as createDossier,
} from '@/routes/workspaces/dossiers';
import type { Paginated } from '@/types/pagination';

export default function ShowClient({
    client,
    dossiers,
}: {
    client: ClientDetails;
    dossiers: Paginated<DossierSummary>;
}) {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();

    setLayoutProps({
        breadcrumbs: [
            { title: t('Clients'), href: clientIndex(currentWorkspace) },
            {
                title: client.name,
                href: showClient({
                    tenant: currentWorkspace,
                    client: client.id,
                }),
            },
        ],
    });

    return (
        <>
            <Head title={client.name} />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex min-w-0 items-start gap-4">
                        <div className="flex size-12 shrink-0 items-center justify-center rounded-xl border bg-secondary text-primary">
                            <span className="text-lg font-semibold" aria-hidden>
                                {client.name.slice(0, 1).toUpperCase()}
                            </span>
                        </div>
                        <div className="min-w-0">
                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                {t('Client record')}
                            </p>
                            <h1 className="truncate text-2xl font-semibold tracking-tight">
                                {client.name}
                            </h1>
                            <p className="mt-1 flex items-center gap-2 text-sm text-muted-foreground">
                                <Mail className="size-4" aria-hidden="true" />
                                {client.email}
                            </p>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link
                                href={editClient({
                                    tenant: currentWorkspace,
                                    client: client.id,
                                })}
                            >
                                <Pencil aria-hidden="true" />
                                {t('Edit client')}
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={createDossier(currentWorkspace)}>
                                {t('New dossier')}
                            </Link>
                        </Button>
                        <ArchiveClientButton
                            clientId={client.id}
                            clientName={client.name}
                            dossiersCount={client.active_dossiers_count}
                        />
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <Card>
                        <CardContent className="flex items-center gap-4 p-5">
                            <div className="flex size-10 items-center justify-center rounded-lg bg-secondary text-primary">
                                <FolderOpen className="size-5" aria-hidden />
                            </div>
                            <div>
                                <p className="text-2xl font-semibold tabular-nums">
                                    {client.active_dossiers_count}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {t('Active dossiers')}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="flex items-center justify-between gap-4 p-5">
                            <div className="flex items-center gap-4">
                                <div className="flex size-10 items-center justify-center rounded-lg bg-muted text-muted-foreground">
                                    <Archive className="size-5" aria-hidden />
                                </div>
                                <div>
                                    <p className="text-2xl font-semibold tabular-nums">
                                        {client.archived_dossiers_count}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {t('Archived dossiers')}
                                    </p>
                                </div>
                            </div>
                            {client.archived_dossiers_count > 0 ? (
                                <Button variant="ghost" size="sm" asChild>
                                    <Link
                                        href={archivedDossiers(
                                            currentWorkspace,
                                            {
                                                query: {
                                                    filter: { q: client.name },
                                                },
                                            },
                                        )}
                                    >
                                        {t('View archive')}
                                    </Link>
                                </Button>
                            ) : null}
                        </CardContent>
                    </Card>
                </div>

                <DossiersListCard dossiers={dossiers} />
                <IndexPagination paginator={dossiers} />
            </div>
        </>
    );
}
