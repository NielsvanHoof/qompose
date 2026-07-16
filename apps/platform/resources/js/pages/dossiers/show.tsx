import { Head, setLayoutProps } from '@inertiajs/react';
import AddDocumentRequestCard from '@/components/dossiers/add-document-request-card';
import ApplyTemplateCard from '@/components/dossiers/apply-template-card';
import DocumentRequestsCard from '@/components/dossiers/document-requests-card';
import DossierWorkflowCard from '@/components/dossiers/dossier-workflow-card';
import Heading from '@/components/heading';
import ClientAccessCard from '@/components/portal/client-access-card';
import PortalLinkBanner from '@/components/portal/portal-link-banner';
import { Badge } from '@/components/ui/badge';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import {
    index as dossierIndex,
    show as showDossier,
} from '@/routes/workspaces/dossiers';
import type { ApplyTemplateOption, Dossier } from '@/types';

/**
 * Staff dossier detail page — composes domain section components.
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

    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dossiers',
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

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={dossier.title}
                        description={`${dossier.client.name} · ${dossier.client.email}`}
                    />
                    <Badge variant="secondary">
                        {dossier.status.replaceAll('_', ' ')}
                    </Badge>
                </div>

                {accessGrantPortalUrl && (
                    <PortalLinkBanner
                        portalUrl={accessGrantPortalUrl}
                        token={accessGrantToken}
                    />
                )}

                <div className="grid gap-6 lg:grid-cols-[1fr_20rem]">
                    <div className="space-y-6">
                        <DocumentRequestsCard
                            dossierId={dossier.id}
                            dossierStatus={dossier.status}
                            documentRequests={dossier.document_requests}
                            canManage={canManage}
                            canReview={canReview}
                            canDownload={canDownload}
                        />

                        <ClientAccessCard
                            dossierId={dossier.id}
                            clientName={dossier.client.name}
                            clientEmail={dossier.client.email}
                            accessGrants={dossier.access_grants}
                            canCreate={
                                canManage && dossier.status !== 'completed'
                            }
                        />
                    </div>

                    <div className="space-y-6">
                        <DossierWorkflowCard
                            dossier={dossier}
                            canReview={canReview}
                        />
                        {canManage && dossier.status !== 'completed' && (
                            <>
                                <ApplyTemplateCard
                                    dossierId={dossier.id}
                                    templates={templates}
                                />
                                <AddDocumentRequestCard
                                    dossierId={dossier.id}
                                />
                            </>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
