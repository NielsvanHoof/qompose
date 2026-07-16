import { Head, setLayoutProps } from '@inertiajs/react';
import CreateDossierForm from '@/components/dossiers/create-dossier-form';
import Heading from '@/components/heading';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import {
    create as createDossier,
    index as dossierIndex,
} from '@/routes/workspaces/dossiers';
import type { DossierClientOption } from '@/types';

/**
 * Create a new dossier for an existing client.
 */
export default function CreateDossier({
    clients,
}: {
    clients: DossierClientOption[];
}) {
    const currentWorkspace = useCurrentWorkspace();

    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dossiers',
                href: dossierIndex(currentWorkspace),
            },
            {
                title: 'New dossier',
                href: createDossier(currentWorkspace),
            },
        ],
    });

    return (
        <>
            <Head title="New dossier" />

            <div className="mx-auto w-full max-w-xl p-4 md:p-8">
                <Heading
                    title="New dossier"
                    description="Create a document collection for an existing client."
                />

                <CreateDossierForm clients={clients} />
            </div>
        </>
    );
}
