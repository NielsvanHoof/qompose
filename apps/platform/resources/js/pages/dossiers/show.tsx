import { Head, setLayoutProps } from '@inertiajs/react';
import DossierShowContent from '@/features/dossiers/show/dossier-show-content';
import type { Dossier } from '@/features/dossiers/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import {
    index as dossierIndex,
    show as showDossier,
} from '@/routes/workspaces/dossiers';

/**
 * Staff dossier overview — follow-up, invite, and links to builder/review.
 */
export default function ShowDossier({
    dossier,
    access_grant_token: accessGrantToken = null,
    access_grant_portal_url: accessGrantPortalUrl = null,
    can_manage: canManage,
    can_edit: canEdit,
    can_send_reminder: canSendReminder,
    can_review: canReview,
    can_download: canDownload,
}: {
    dossier: Dossier;
    templates?: unknown[];
    access_grant_token?: string | null;
    access_grant_portal_url?: string | null;
    can_manage: boolean;
    can_edit: boolean;
    can_send_reminder: boolean;
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
                accessGrantToken={accessGrantToken}
                accessGrantPortalUrl={accessGrantPortalUrl}
                canManage={canManage}
                canEdit={canEdit}
                canSendReminder={canSendReminder}
                canReview={canReview}
                canDownload={canDownload}
            />
        </>
    );
}
