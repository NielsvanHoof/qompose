import { Head, Link, setLayoutProps, usePoll } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useEffect } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import ReviewItemsPanel from '@/features/document-requests/staff/review/review-items-panel';
import { DossierPermissionsProvider } from '@/features/dossiers/permissions/dossier-permissions-context';
import DossierWorkflowCard from '@/features/dossiers/show/dossier-workflow-card';
import DossierStatusBadge from '@/features/dossiers/status/dossier-status-badge';
import type { Dossier } from '@/features/dossiers/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import {
    index as dossierIndex,
    show as showDossier,
} from '@/routes/workspaces/dossiers';

/**
 * Focused review queue for a dossier's questionnaire items.
 */
export default function ReviewDossier({
    dossier,
    can_manage: canManage,
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
            {
                title: t('Review'),
                href: '#',
            },
        ],
    });

    // Poll while OCR is still running so extracted JSON appears without a refresh.
    const hasInFlightProcessing = dossier.document_requests.some((request) => {
        const status = request.uploaded_document?.processing_status;

        return status === 'pending' || status === 'processing';
    });

    const { start, stop } = usePoll(
        2000,
        { only: ['dossier'] },
        { autoStart: false, mode: 'rest' },
    );

    useEffect(() => {
        if (hasInFlightProcessing) {
            start();
        } else {
            stop();
        }
    }, [hasInFlightProcessing, start, stop]);

    return (
        <>
            <Head title={`${t('Review')} · ${dossier.title}`} />
            <DossierPermissionsProvider
                value={{ canManage, canReview, canDownload }}
            >
                <div className="mx-auto flex w-full max-w-6xl flex-col gap-6 p-4 md:p-8">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div className="space-y-3">
                            <Button
                                variant="ghost"
                                size="sm"
                                className="px-0"
                                asChild
                            >
                                <Link
                                    href={showDossier({
                                        tenant: currentWorkspace,
                                        dossier: dossier.id,
                                    })}
                                >
                                    <ArrowLeft aria-hidden="true" />
                                    {t('Back to dossier')}
                                </Link>
                            </Button>
                            <Heading
                                level={1}
                                title={t('Review')}
                                description={`${dossier.title} · ${dossier.client.name}`}
                            />
                        </div>
                        <DossierStatusBadge status={dossier.status} />
                    </div>

                    <div className="grid gap-6 lg:grid-cols-[1fr_20rem]">
                        <ReviewItemsPanel
                            dossierId={dossier.id}
                            dossierStatus={dossier.status}
                            documentRequests={dossier.document_requests}
                        />
                        <DossierWorkflowCard dossier={dossier} />
                    </div>
                </div>
            </DossierPermissionsProvider>
        </>
    );
}
