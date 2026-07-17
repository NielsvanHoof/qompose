import { Head, setLayoutProps, usePoll } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AddDocumentRequestCard from '@/features/document-requests/staff/add-document-request-card';
import DocumentRequestsCard from '@/features/document-requests/staff/document-requests-card';
import ApplyTemplateCard from '@/features/dossiers/apply-template-card';
import DossierWorkflowCard from '@/features/dossiers/dossier-workflow-card';
import type { Dossier, DossierStatus } from '@/features/dossiers/types';
import WaitingItemsSummary from '@/features/dossiers/waiting-items-summary';
import ClientAccessCard from '@/features/portal/client-access-card';
import PortalLinkBanner from '@/features/portal/portal-link-banner';
import type { ApplyTemplateOption } from '@/features/questionnaires/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import {
    index as dossierIndex,
    show as showDossier,
} from '@/routes/workspaces/dossiers';

type DossierStageTab = 'prepare' | 'invite' | 'review';

/**
 * Pick the tab that matches where the dossier is in the workflow.
 */
function defaultTabForStatus(status: DossierStatus): DossierStageTab {
    switch (status) {
        case 'awaiting_client':
            return 'invite';
        case 'in_review':
        case 'completed':
            return 'review';
        default:
            return 'prepare';
    }
}

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
    const [tab, setTab] = useState<DossierStageTab>(() =>
        defaultTabForStatus(dossier.status),
    );

    // Poll while OCR is still running so extracted JSON appears without a refresh.
    const hasInFlightProcessing = dossier.document_requests.some((request) => {
        const status = request.uploaded_document?.processing_status;

        return status === 'pending' || status === 'processing';
    });

    const { start, stop } = usePoll(
        2000,
        {
            only: ['dossier'],
        },
        {
            autoStart: false,
            mode: 'rest',
        },
    );

    useEffect(() => {
        if (hasInFlightProcessing) {
            start();
        } else {
            stop();
        }
    }, [hasInFlightProcessing, start, stop]);

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

    const canEditStructure = canManage && dossier.status !== 'completed';

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

                <Tabs
                    value={tab}
                    onValueChange={(value) => setTab(value as DossierStageTab)}
                >
                    <TabsList>
                        <TabsTrigger value="prepare">Prepare</TabsTrigger>
                        <TabsTrigger value="invite">Invite</TabsTrigger>
                        <TabsTrigger value="review">Review</TabsTrigger>
                    </TabsList>

                    <p className="text-sm text-muted-foreground">
                        {tab === 'prepare' &&
                            'Build the questionnaire, then invite the client.'}
                        {tab === 'invite' &&
                            'Send a portal link so the client can upload. After uploads arrive, use Review.'}
                        {tab === 'review' &&
                            'Check OCR results, approve or request changes, then complete the dossier.'}
                    </p>

                    <TabsContent value="prepare">
                        <div className="grid gap-6 lg:grid-cols-[1fr_20rem]">
                            <DocumentRequestsCard
                                dossierId={dossier.id}
                                dossierStatus={dossier.status}
                                documentRequests={dossier.document_requests}
                                canManage={canManage}
                                canReview={canReview}
                                canDownload={canDownload}
                            />
                            <div className="space-y-6">
                                {canEditStructure && (
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
                    </TabsContent>

                    <TabsContent value="invite">
                        <div className="grid gap-6 lg:grid-cols-[1fr_20rem]">
                            <ClientAccessCard
                                dossierId={dossier.id}
                                clientName={dossier.client.name}
                                clientEmail={dossier.client.email}
                                accessGrants={dossier.access_grants}
                                canCreate={canEditStructure}
                            />
                            <WaitingItemsSummary
                                documentRequests={dossier.document_requests}
                            />
                        </div>
                    </TabsContent>

                    <TabsContent value="review">
                        <div className="grid gap-6 lg:grid-cols-[1fr_20rem]">
                            <DocumentRequestsCard
                                dossierId={dossier.id}
                                dossierStatus={dossier.status}
                                documentRequests={dossier.document_requests}
                                canManage={canManage}
                                canReview={canReview}
                                canDownload={canDownload}
                            />
                            <DossierWorkflowCard
                                dossier={dossier}
                                canReview={canReview}
                            />
                        </div>
                    </TabsContent>
                </Tabs>
            </div>
        </>
    );
}
