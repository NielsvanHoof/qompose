import { Head, setLayoutProps } from '@inertiajs/react';
import Heading from '@/components/heading';
import EditDossierForm from '@/features/dossiers/edit/edit-dossier-form';
import type { EditableDossier } from '@/features/dossiers/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import {
    index as dossierIndex,
    edit as editDossier,
    show as showDossier,
} from '@/routes/workspaces/dossiers';

export default function EditDossier({ dossier }: { dossier: EditableDossier }) {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();

    setLayoutProps({
        breadcrumbs: [
            { title: t('Dossiers'), href: dossierIndex(currentWorkspace) },
            {
                title: dossier.title,
                href: showDossier({
                    tenant: currentWorkspace,
                    dossier: dossier.id,
                }),
            },
            {
                title: t('Edit'),
                href: editDossier({
                    tenant: currentWorkspace,
                    dossier: dossier.id,
                }),
            },
        ],
    });

    return (
        <>
            <Head title={t('Edit :title', { title: dossier.title })} />

            <div className="mx-auto w-full max-w-xl p-4 md:p-8">
                <Heading
                    title={t('Edit dossier')}
                    description={t(
                        'Update the working title or reference without changing the client relationship.',
                    )}
                />
                <EditDossierForm dossier={dossier} />
            </div>
        </>
    );
}
