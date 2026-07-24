import { Head, setLayoutProps } from '@inertiajs/react';
import QuestionnaireBuilder from '@/features/document-requests/staff/builder/questionnaire-builder';
import type { Dossier } from '@/features/dossiers/types';
import type { ApplyTemplateOption } from '@/features/questionnaires/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import { show as showDossier } from '@/routes/workspaces/dossiers';

/**
 * Full-bleed questionnaire builder for a dossier.
 */
export default function BuilderDossier({
    dossier,
    templates = [],
    can_manage: canManage,
}: {
    dossier: Dossier;
    templates?: ApplyTemplateOption[];
    can_manage: boolean;
    can_edit: boolean;
    can_send_reminder: boolean;
    can_review: boolean;
    can_download: boolean;
}) {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();
    const canEditStructure = canManage && dossier.status !== 'completed';

    setLayoutProps({
        backHref: showDossier({
            tenant: currentWorkspace,
            dossier: dossier.id,
        }).url,
        title: dossier.title,
        status: dossier.status,
    });

    return (
        <>
            <Head title={`${t('Build Form')} · ${dossier.title}`} />
            {/* flex-1 + min-h-0 so the builder fills the layout below the header. */}
            <div className="flex min-h-0 w-full flex-1 flex-col p-4 md:p-6">
                <QuestionnaireBuilder
                    dossierId={dossier.id}
                    dossierStatus={dossier.status}
                    documentRequests={dossier.document_requests}
                    templates={templates}
                    canEdit={canEditStructure}
                />
            </div>
        </>
    );
}
