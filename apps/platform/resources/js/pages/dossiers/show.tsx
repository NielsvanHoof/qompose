import { Head, setLayoutProps } from '@inertiajs/react';
import DossierShowContent from '@/features/dossiers/show/dossier-show-content';
import type { Dossier } from '@/features/dossiers/types';
import type { ApplyTemplateOption } from '@/features/questionnaires/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import {
    index as dossierIndex,
    show as showDossier,
} from '@/routes/workspaces/dossiers';

/**
 * Staff dossier detail — stage tabs for Prepare → Invite → Review.
 */
export default function ShowDossier({
    dossier,
    templates = [],
    access_grant_token: accessGrantToken = null,
    access_grant_portal_url: accessGrantPortalUrl = null,
    can_manage: canManage,
    can_review: canReview,
    can_download: canDownload,
}: {
    dossier: Dossier;
    templates?: ApplyTemplateOption[];
    access_grant_token?: string | null;
    access_grant_portal_url?: string | null;
    can_manage: boolean;
    can_review: boolean;
    can_download: boolean;
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
                title: dossier.title,
                href: showDossier({
                    tenant: currentWorkspace,
                    dossier: dossier.id,
                }),
            },
        ],
    });

    return (
        <>
            <Head title={dossier.title} />
            <DossierShowContent
                dossier={dossier}
                templates={templates}
                accessGrantToken={accessGrantToken}
                accessGrantPortalUrl={accessGrantPortalUrl}
                canManage={canManage}
                canReview={canReview}
                canDownload={canDownload}
            />
        </>
    );
}
