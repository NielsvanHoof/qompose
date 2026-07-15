import { Head } from '@inertiajs/react';
import CreateDossierForm from '@/components/dossiers/create-dossier-form';
import Heading from '@/components/heading';
import type { DossierClientOption } from '@/types';
import {
    create as createDossier,
    index as dossierIndex,
} from '@/routes/workspaces/dossiers';

/**
 * Create a new dossier for an existing client.
 */
export default function CreateDossier({
    clients,
}: {
    clients: DossierClientOption[];
}) {
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

CreateDossier.layout = {
    breadcrumbs: [
        {
            title: 'Dossiers',
            href: dossierIndex(),
        },
        {
            title: 'New dossier',
            href: createDossier(),
        },
    ],
};
