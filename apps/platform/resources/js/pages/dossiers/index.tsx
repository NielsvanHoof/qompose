import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import DossiersListCard from '@/components/dossiers/dossiers-list-card';
import { Button } from '@/components/ui/button';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { index as clientIndex } from '@/routes/workspaces/clients';
import { create, index as dossierIndex } from '@/routes/workspaces/dossiers';
import type { DossierSummary } from '@/types';

/**
 * Dossiers index — list of all firm dossiers.
 */
export default function DossierIndex({
    dossiers,
}: {
    dossiers: DossierSummary[];
}) {
    const currentWorkspace = useCurrentWorkspace();

    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dossiers',
                href: dossierIndex(currentWorkspace),
            },
        ],
    });

    return (
        <>
            <Head title="Dossiers" />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Dossiers
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Request and review documents for each client.
                        </p>
                    </div>

                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={clientIndex(currentWorkspace)}>
                                Clients
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={create(currentWorkspace)}>
                                <Plus />
                                New dossier
                            </Link>
                        </Button>
                    </div>
                </div>

                <DossiersListCard dossiers={dossiers} />
            </div>
        </>
    );
}
