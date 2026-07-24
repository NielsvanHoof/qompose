import { Link } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import DossierFollowUpCard from '@/features/dossiers/follow-up/dossier-follow-up-card';
import {
    DossierPermissionsProvider,
    useDossierPermissions,
} from '@/features/dossiers/permissions/dossier-permissions-context';
import ArchiveDossierButton from '@/features/dossiers/show/archive-dossier-button';
import DossierWorkflowLinks from '@/features/dossiers/show/dossier-workflow-links';
import WaitingItemsSummary from '@/features/dossiers/show/waiting-items-summary';
import DossierStatusBadge from '@/features/dossiers/status/dossier-status-badge';
import type { Dossier } from '@/features/dossiers/types';
import ClientAccessCard from '@/features/portal/client-access-card';
import PortalLinkBanner from '@/features/portal/portal-link-banner';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import { edit as editDossier } from '@/routes/workspaces/dossiers';

type DossierShowContentProps = {
    dossier: Dossier;
    accessGrantToken?: string | null;
    accessGrantPortalUrl?: string | null;
    canManage: boolean;
    canEdit: boolean;
    canSendReminder: boolean;
    canReview: boolean;
    canDownload: boolean;
};

/**
 * Staff dossier overview hub — follow-up, invite, and links out to builder/review.
 */
export default function DossierShowContent({
    dossier,
    accessGrantToken = null,
    accessGrantPortalUrl = null,
    canManage,
    canEdit,
    canSendReminder,
    canReview,
    canDownload,
}: DossierShowContentProps) {
    return (
        <DossierPermissionsProvider
            value={{ canManage, canReview, canDownload }}
        >
            <DossierShowBody
                dossier={dossier}
                accessGrantToken={accessGrantToken}
                accessGrantPortalUrl={accessGrantPortalUrl}
                canEdit={canEdit}
                canSendReminder={canSendReminder}
            />
        </DossierPermissionsProvider>
    );
}

function DossierShowBody({
    dossier,
    accessGrantToken,
    accessGrantPortalUrl,
    canEdit,
    canSendReminder,
}: {
    dossier: Dossier;
    accessGrantToken: string | null;
    accessGrantPortalUrl: string | null;
    canEdit: boolean;
    canSendReminder: boolean;
}) {
    const { t } = useTranslation();
    const { canManage } = useDossierPermissions();
    const currentWorkspace = useCurrentWorkspace();

    const canEditStructure = canManage && dossier.status !== 'completed';
    const hasFormItems = dossier.document_requests.length > 0;
    const canCreateInvite = canEditStructure && hasFormItems;

    return (
        <div className="mx-auto flex w-full max-w-6xl flex-col gap-6 p-4 md:p-8">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <Heading
                    level={1}
                    title={dossier.title}
                    description={`${dossier.client.name} · ${dossier.client.email}`}
                />
                <div className="flex flex-wrap items-center gap-2">
                    <DossierStatusBadge status={dossier.status} />
                    {canEdit ? (
                        <Button variant="outline" asChild>
                            <Link
                                href={editDossier({
                                    tenant: currentWorkspace,
                                    dossier: dossier.id,
                                })}
                            >
                                <Pencil aria-hidden="true" />
                                {t('Edit dossier')}
                            </Link>
                        </Button>
                    ) : null}
                    {canManage ? (
                        <ArchiveDossierButton
                            dossierId={dossier.id}
                            dossierTitle={dossier.title}
                        />
                    ) : null}
                </div>
            </div>

            <DossierFollowUpCard
                dossier={dossier}
                canSendReminder={canSendReminder}
            />

            {accessGrantPortalUrl ? (
                <PortalLinkBanner
                    portalUrl={accessGrantPortalUrl}
                    token={accessGrantToken}
                />
            ) : null}

            <DossierWorkflowLinks dossier={dossier} />

            <section
                id="dossier-invite"
                className="grid scroll-mt-6 gap-6 lg:grid-cols-[1fr_20rem]"
                aria-labelledby="dossier-invite-heading"
            >
                <div className="space-y-3">
                    <h2
                        id="dossier-invite-heading"
                        className="text-base font-semibold tracking-tight"
                    >
                        {t('Send Invite')}
                    </h2>
                    {!hasFormItems ? (
                        <p className="rounded-md border border-border/70 bg-muted/30 px-3 py-2 text-sm text-muted-foreground">
                            {t(
                                'Add at least one form field before inviting the client.',
                            )}
                        </p>
                    ) : null}
                    <ClientAccessCard
                        dossierId={dossier.id}
                        clientName={dossier.client.name}
                        clientEmail={dossier.client.email}
                        accessGrants={dossier.access_grants}
                        canCreate={canCreateInvite}
                    />
                </div>
                <WaitingItemsSummary
                    documentRequests={dossier.document_requests}
                />
            </section>
        </div>
    );
}
