import { usePoll } from '@inertiajs/react';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { useEffect } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import AddDocumentRequestCard from '@/features/document-requests/staff/add-document-request-card';
import DocumentRequestsCard from '@/features/document-requests/staff/document-requests-card';
import {
    DossierPermissionsProvider,
    useDossierPermissions,
} from '@/features/dossiers/permissions/dossier-permissions-context';
import ApplyTemplateCard from '@/features/dossiers/show/apply-template-card';
import ArchiveDossierButton from '@/features/dossiers/show/archive-dossier-button';
import DossierWorkflowCard from '@/features/dossiers/show/dossier-workflow-card';
import WaitingItemsSummary from '@/features/dossiers/show/waiting-items-summary';
import {
    dossierStageHint,
    dossierStageLabel,
    STAGE_ORDER,
} from '@/features/dossiers/stage/dossier-stage';
import DossierStageProgress from '@/features/dossiers/stage/dossier-stage-progress';
import DossierStatusBadge from '@/features/dossiers/status/dossier-status-badge';
import type { Dossier } from '@/features/dossiers/types';
import ClientAccessCard from '@/features/portal/client-access-card';
import PortalLinkBanner from '@/features/portal/portal-link-banner';
import type { ApplyTemplateOption } from '@/features/questionnaires/types';
import { useDossierStageTab } from '@/hooks/use-dossier-stage-tab';
import { useTranslation } from '@/hooks/use-translation';

type DossierShowContentProps = {
    dossier: Dossier;
    templates?: ApplyTemplateOption[];
    accessGrantToken?: string | null;
    accessGrantPortalUrl?: string | null;
    canManage: boolean;
    canReview: boolean;
    canDownload: boolean;
};

/**
 * Staff dossier detail body — stage tabs for Prepare → Invite → Review.
 */
export default function DossierShowContent({
    dossier,
    templates = [],
    accessGrantToken = null,
    accessGrantPortalUrl = null,
    canManage,
    canReview,
    canDownload,
}: DossierShowContentProps) {
    return (
        <DossierPermissionsProvider
            value={{ canManage, canReview, canDownload }}
        >
            <DossierShowBody
                dossier={dossier}
                templates={templates}
                accessGrantToken={accessGrantToken}
                accessGrantPortalUrl={accessGrantPortalUrl}
            />
        </DossierPermissionsProvider>
    );
}

function DossierShowBody({
    dossier,
    templates,
    accessGrantToken,
    accessGrantPortalUrl,
}: {
    dossier: Dossier;
    templates: ApplyTemplateOption[];
    accessGrantToken: string | null;
    accessGrantPortalUrl: string | null;
}) {
    const { t } = useTranslation();
    const { canManage } = useDossierPermissions();
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
        <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <Heading
                    level={1}
                    title={dossier.title}
                    description={`${dossier.client.name} · ${dossier.client.email}`}
                />
                <div className="flex flex-wrap items-center gap-2">
                    <DossierStatusBadge status={dossier.status} />
                    {canManage && (
                        <ArchiveDossierButton
                            dossierId={dossier.id}
                            dossierTitle={dossier.title}
                        />
                    )}
                </div>
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

                <p aria-live="polite" className="text-sm text-muted-foreground">
                    {dossierStageHint(tab, t)}
                </p>

                {tab === 'prepare' && (
                    <div className="grid gap-6 lg:grid-cols-[1fr_20rem]">
                        <DocumentRequestsCard
                            dossierId={dossier.id}
                            dossierStatus={dossier.status}
                            documentRequests={dossier.document_requests}
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
                        />
                        <DossierWorkflowCard dossier={dossier} />
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
                                    stage: dossierStageLabel(previousStage, t),
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
    );
}
