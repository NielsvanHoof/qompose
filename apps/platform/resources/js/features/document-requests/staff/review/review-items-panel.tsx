import EmptyState from '@/components/empty-state';
import { Badge } from '@/components/ui/badge';
import { getQuestionnaireItemTypeDefinition } from '@/features/document-requests/questionnaire-item-type-registry';
import DocumentRequestReview from '@/features/document-requests/staff/review/document-request-review';
import { documentRequestStatusLabel } from '@/features/document-requests/status/document-request-status';
import type { DocumentRequest } from '@/features/document-requests/types';
import { useDossierPermissions } from '@/features/dossiers/permissions/dossier-permissions-context';
import type { DossierStatus } from '@/features/dossiers/types';
import { useTranslation } from '@/hooks/use-translation';

/**
 * Review-stage list: responses, OCR, staff file uploads, and review actions.
 * Field definitions remain read-only here.
 */
export default function ReviewItemsPanel({
    dossierId,
    dossierStatus,
    documentRequests,
}: {
    dossierId: number;
    dossierStatus: DossierStatus;
    documentRequests: DocumentRequest[];
}) {
    const { t } = useTranslation();
    const { canManage, canReview } = useDossierPermissions();
    const reviewAllowed = canReview && dossierStatus !== 'completed';
    const staffUploadAllowed = canManage && dossierStatus !== 'completed';

    const ready = documentRequests.filter(
        (request) => request.status === 'submitted',
    );
    const waiting = documentRequests.filter(
        (request) =>
            request.status === 'pending' || request.status === 'rejected',
    );
    const approved = documentRequests.filter(
        (request) => request.status === 'accepted',
    );

    return (
        <section
            className="space-y-5 rounded-2xl border border-border/70 bg-card p-4 md:p-5"
            aria-labelledby="review-queue-heading"
        >
            <div>
                <h3
                    id="review-queue-heading"
                    className="text-base font-semibold tracking-tight"
                >
                    {t('Review queue')}
                </h3>
                <p className="mt-1 text-sm text-muted-foreground">
                    {t('Approve items or request changes, then complete.')}
                </p>
            </div>

            {documentRequests.length === 0 ? (
                <EmptyState
                    variant="panel"
                    title={t('No questionnaire items on this dossier.')}
                />
            ) : (
                <>
                    <ReviewGroup
                        title={t('Ready to review (:count)', {
                            count: ready.length,
                        })}
                        emptyLabel={t('No items ready to review.')}
                        items={ready}
                        dossierId={dossierId}
                        dossierStatus={dossierStatus}
                        canReview={reviewAllowed}
                        canUpload={staffUploadAllowed}
                    />
                    <ReviewGroup
                        title={t('Waiting on client (:count)', {
                            count: waiting.length,
                        })}
                        emptyLabel={t('Nothing waiting on the client.')}
                        items={waiting}
                        dossierId={dossierId}
                        dossierStatus={dossierStatus}
                        canReview={reviewAllowed}
                        canUpload={staffUploadAllowed}
                    />
                    <ReviewGroup
                        title={t('Approved (:count)', {
                            count: approved.length,
                        })}
                        emptyLabel={t('No approved items yet.')}
                        items={approved}
                        dossierId={dossierId}
                        dossierStatus={dossierStatus}
                        canReview={reviewAllowed}
                        canUpload={staffUploadAllowed}
                    />
                </>
            )}
        </section>
    );
}

function ReviewGroup({
    title,
    emptyLabel,
    items,
    dossierId,
    dossierStatus,
    canReview,
    canUpload,
}: {
    title: string;
    emptyLabel: string;
    items: DocumentRequest[];
    dossierId: number;
    dossierStatus: DossierStatus;
    canReview: boolean;
    canUpload: boolean;
}) {
    const { t } = useTranslation();

    return (
        <section className="space-y-2">
            <h4 className="text-sm font-medium">{title}</h4>
            {items.length === 0 ? (
                <p className="text-sm text-muted-foreground">{emptyLabel}</p>
            ) : (
                <ul className="divide-y overflow-hidden rounded-xl border border-border/70">
                    {items.map((documentRequest) => {
                        const definition = getQuestionnaireItemTypeDefinition(
                            documentRequest.type,
                        );
                        const { StaffContent } = definition;

                        return (
                            <li
                                key={documentRequest.id}
                                className="space-y-3 px-4 py-3"
                            >
                                <div className="flex flex-wrap items-center gap-2">
                                    <p className="min-w-0 truncate text-sm font-medium">
                                        {documentRequest.title}
                                    </p>
                                    <Badge variant="outline">
                                        {t(definition.label)}
                                    </Badge>
                                    <Badge variant="secondary">
                                        {documentRequestStatusLabel(
                                            documentRequest.status,
                                            t,
                                        )}
                                    </Badge>
                                </div>
                                <StaffContent
                                    dossierId={dossierId}
                                    dossierStatus={dossierStatus}
                                    documentRequest={documentRequest}
                                    // Renderers use this only for optional
                                    // staff responses. Field definitions stay
                                    // read-only in Review.
                                    canEdit={
                                        canUpload &&
                                        documentRequest.status !== 'accepted'
                                    }
                                />
                                <DocumentRequestReview
                                    dossierId={dossierId}
                                    documentRequest={documentRequest}
                                    canReview={canReview}
                                />
                            </li>
                        );
                    })}
                </ul>
            )}
        </section>
    );
}
