import { Head, setLayoutProps, usePoll } from '@inertiajs/react';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { useEffect } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import AddDocumentRequestCard from '@/features/document-requests/staff/add-document-request-card';
import DocumentRequestsCard from '@/features/document-requests/staff/document-requests-card';
import ApplyTemplateCard from '@/features/dossiers/apply-template-card';
import {
    dossierStageHint,
    dossierStageLabel,
    STAGE_ORDER,
} from '@/features/dossiers/dossier-stage';
import DossierStageProgress from '@/features/dossiers/dossier-stage-progress';
import DossierStatusBadge from '@/features/dossiers/dossier-status-badge';
import DossierWorkflowCard from '@/features/dossiers/dossier-workflow-card';
import type { Dossier } from '@/features/dossiers/types';
import WaitingItemsSummary from '@/features/dossiers/waiting-items-summary';
import ClientAccessCard from '@/features/portal/client-access-card';
import PortalLinkBanner from '@/features/portal/portal-link-banner';
import type { ApplyTemplateOption } from '@/features/questionnaires/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useDossierStageTab } from '@/hooks/use-dossier-stage-tab';
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
    const [tab, setTab] = useDossierStageTab(dossier.status);

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

    const canEditStructure = canManage && dossier.status !== 'completed';
    const stageIndex = STAGE_ORDER.indexOf(tab);
    const previousStage = stageIndex > 0 ? STAGE_ORDER[stageIndex - 1] : null;
    const nextStage =
        stageIndex < STAGE_ORDER.length - 1
            ? STAGE_ORDER[stageIndex + 1]
            : null;

    // Soft gate: don't advance to Invite with an empty questionnaire.
    const canAdvanceFromPrepare = dossier.document_requests.length > 0;
    const nextDisabled = tab === 'prepare' && !canAdvanceFromPrepare;

    return (
        <>
            <Head title={dossier.title} />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        level={1}
                        title={dossier.title}
                        description={`${dossier.client.name} · ${dossier.client.email}`}
                    />
                    <DossierStatusBadge status={dossier.status} />
                </div>

                {accessGrantPortalUrl && (
                    <PortalLinkBanner
                        portalUrl={accessGrantPortalUrl}
                        token={accessGrantToken}
                    />
                )}

                <div className="flex flex-col gap-4">
                    <DossierStageProgress
                        dossier={dossier}
                        activeTab={tab}
                        onStageSelect={setTab}
                    />

                    <p
                        aria-live="polite"
                        className="text-sm text-muted-foreground"
                    >
                        {dossierStageHint(tab, t)}
                    </p>

                    {tab === 'prepare' && (
                        <div className="grid gap-6 lg:grid-cols-[1fr_20rem]">
                            <DocumentRequestsCard
                                dossierId={dossier.id}
                                dossierStatus={dossier.status}
                                documentRequests={dossier.document_requests}
                                canManage={canManage}
                                canReview={canReview}
                                canDownload={canDownload}
                            />
                            <div className="min-w-0 space-y-6">
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
                    )}

                    {tab === 'invite' && (
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
                    )}

                    {tab === 'review' && (
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
                    )}

                    {/* Linear stage controls — progress nav allows jumping back. */}
                    <div className="flex flex-wrap items-center justify-between gap-3 border-t pt-4">
                        <div>
                            {previousStage ? (
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setTab(previousStage)}
                                >
                                    <ArrowLeft aria-hidden="true" />
                                    {t('Back to :stage', {
                                        stage: dossierStageLabel(
                                            previousStage,
                                            t,
                                        ),
                                    })}
                                </Button>
                            ) : (
                                <span />
                            )}
                        </div>

                        <div className="flex flex-col items-end gap-1">
                            {nextStage ? (
                                <Button
                                    type="button"
                                    disabled={nextDisabled}
                                    onClick={() => setTab(nextStage)}
                                >
                                    {t('Next: :stage', {
                                        stage: dossierStageLabel(nextStage, t),
                                    })}
                                    <ArrowRight aria-hidden="true" />
                                </Button>
                            ) : null}
                            {nextDisabled && (
                                <p className="text-xs text-muted-foreground">
                                    {t(
                                        'Add at least one questionnaire item first.',
                                    )}
                                </p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
