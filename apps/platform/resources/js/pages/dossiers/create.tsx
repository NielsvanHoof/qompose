import { Head, setLayoutProps } from '@inertiajs/react';
import Heading from '@/components/heading';
import CreateDossierForm from '@/features/dossiers/create/create-dossier-form';
import type { DossierClientOption } from '@/features/dossiers/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
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
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();

    setLayoutProps({
        breadcrumbs: [
            {
                title: t('Dossiers'),
                href: dossierIndex(currentWorkspace),
            },
            {
                title: t('New dossier'),
                href: createDossier(currentWorkspace),
            },
        ],
    });

    return (
        <>
            <Head title={t('New dossier')} />

            <div className="mx-auto w-full max-w-xl p-4 md:p-8">
                <Heading
                    title={t('New dossier')}
                    description={t(
                        'Create a document collection for an existing client.',
                    )}
                />

                <CreateDossierForm clients={clients} />
            </div>
        </>
    );
}
