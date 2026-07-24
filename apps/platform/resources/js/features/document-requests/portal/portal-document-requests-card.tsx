import EmptyState from '@/components/empty-state';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { getQuestionnaireItemTypeDefinition } from '@/features/document-requests/questionnaire-item-type-registry';
import { documentRequestStatusLabel } from '@/features/document-requests/status/document-request-status';
import type { PortalDocumentRequest } from '@/features/document-requests/types';
import { storesAnswerText } from '@/features/document-requests/types';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

/**
 * Client-portal questionnaire — numbered field cards aligned with the staff builder canvas.
 */
export default function PortalDocumentRequestsCard({
    firmName,
    documentRequests,
    nextIncompleteRequestId,
}: {
    firmName: string;
    documentRequests: PortalDocumentRequest[];
    nextIncompleteRequestId: number | null;
}) {
    const { t } = useTranslation();

    return (
        <section className="space-y-3" aria-label={t('Questionnaire')}>
            <div className="space-y-1">
                <div className="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 className="text-sm font-semibold tracking-tight">
                        {t('Your form')}
                    </h2>
                    <p className="font-data text-xs text-muted-foreground tabular-nums">
                        {t(':count fields', { count: documentRequests.length })}
                    </p>
                </div>
                <p className="text-xs text-muted-foreground text-pretty">
                    {t(
                        'Complete each item. Submitted items stay locked while they are reviewed.',
                    )}
                </p>
            </div>

            {documentRequests.length === 0 ? (
                <EmptyState
                    variant="panel"
                    title={t(
                        'Nothing has been requested yet. Check back later or contact :firm.',
                        { firm: firmName },
                    )}
                />
            ) : (
                <ol className="space-y-3">
                    {documentRequests.map((documentRequest, index) => {
                        const isNext =
                            documentRequest.id === nextIncompleteRequestId;
                        const definition = getQuestionnaireItemTypeDefinition(
                            documentRequest.type,
                        );

                        return (
                            <li
                                key={documentRequest.id}
                                id={`request-${documentRequest.id}`}
                                className={cn(
                                    'scroll-mt-6 list-none rounded-xl border bg-card shadow-xs transition-colors duration-150 motion-reduce:transition-none',
                                    isNext
                                        ? 'border-primary ring-[3px] ring-ring/40'
                                        : 'border-border/70',
                                )}
                            >
                                <div className="flex items-start gap-2 px-3 py-2.5 md:px-3.5">
                                    <span className="mt-0.5 w-5 shrink-0 font-data text-xs text-muted-foreground tabular-nums">
                                        {index + 1}
                                    </span>
                                    <div className="min-w-0 flex-1 space-y-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <h3 className="min-w-0 flex-1 text-sm font-medium text-pretty">
                                                {documentRequest.title}
                                            </h3>
                                            <Badge
                                                variant="outline"
                                                className="shrink-0"
                                            >
                                                {t(definition.label)}
                                            </Badge>
                                            <StatusBadge
                                                status={documentRequest.status}
                                            />
                                        </div>
                                        {documentRequest.instructions ? (
                                            <p className="text-xs text-muted-foreground text-pretty">
                                                {documentRequest.instructions}
                                            </p>
                                        ) : null}
                                    </div>
                                </div>

                                <div className="space-y-3 border-t border-border/60 px-3 py-3 md:px-3.5">
                                    {documentRequest.status === 'rejected' &&
                                    documentRequest.rejection_reason ? (
                                        <Alert variant="destructive">
                                            <AlertTitle>
                                                {t('Changes requested')}
                                            </AlertTitle>
                                            <AlertDescription>
                                                {
                                                    documentRequest.rejection_reason
                                                }
                                            </AlertDescription>
                                        </Alert>
                                    ) : null}

                                    <PortalRequestTypeContent
                                        documentRequest={documentRequest}
                                        canRespond={documentRequest.can_respond}
                                    />

                                    <FieldFooter
                                        firmName={firmName}
                                        documentRequest={documentRequest}
                                    />
                                </div>
                            </li>
                        );
                    })}
                </ol>
            )}
        </section>
    );
}

function StatusBadge({ status }: { status: PortalDocumentRequest['status'] }) {
    const { t } = useTranslation();

    return (
        <Badge
            variant="outline"
            className={cn(
                'shrink-0',
                status === 'accepted' &&
                    'border-success-border bg-success-muted text-success-foreground',
                status === 'rejected' &&
                    'border-destructive/30 bg-destructive/5 text-destructive',
                status === 'submitted' && 'bg-muted/60',
            )}
        >
            {documentRequestStatusLabel(status, t)}
        </Badge>
    );
}

function FieldFooter({
    firmName,
    documentRequest,
}: {
    firmName: string;
    documentRequest: PortalDocumentRequest;
}) {
    const { t } = useTranslation();

    if (documentRequest.can_respond) {
        return null;
    }

    if (documentRequest.status === 'submitted') {
        return (
            <p className="text-xs text-muted-foreground">
                {t('Submitted and waiting for review.')}
            </p>
        );
    }

    if (documentRequest.status === 'accepted') {
        return (
            <p className="text-xs text-success-foreground">
                {t('Approved by :firm.', { firm: firmName })}
            </p>
        );
    }

    return null;
}

/**
 * Resolve type-specific portal content through the exhaustive registry.
 */
function PortalRequestTypeContent({
    documentRequest,
    canRespond,
}: {
    documentRequest: PortalDocumentRequest;
    canRespond: boolean;
}) {
    const { PortalContent } = getQuestionnaireItemTypeDefinition(
        documentRequest.type,
    );

    return (
        <PortalContent
            documentRequest={documentRequest}
            canRespond={canRespond}
        />
    );
}

/**
 * Read-only answer preview when the client can no longer edit this item.
 */
export function PortalSubmittedAnswerPreview({
    documentRequest,
}: {
    documentRequest: PortalDocumentRequest;
}) {
    const { t } = useTranslation();

    if (storesAnswerText(documentRequest.type)) {
        if (!documentRequest.answer_text) {
            return null;
        }

        return (
            <div className="rounded-lg bg-muted/50 px-3 py-2.5 text-sm">
                <p className="text-xs text-muted-foreground">{t('Your answer')}</p>
                <p className="mt-0.5 font-medium whitespace-pre-wrap">
                    {documentRequest.answer_text}
                </p>
            </div>
        );
    }

    if (
        documentRequest.type === 'boolean' &&
        documentRequest.answer_boolean !== null
    ) {
        return (
            <div className="rounded-lg bg-muted/50 px-3 py-2.5 text-sm">
                <p className="text-xs text-muted-foreground">{t('Your answer')}</p>
                <p className="mt-0.5 font-medium">
                    {documentRequest.answer_boolean ? t('Yes') : t('No')}
                </p>
            </div>
        );
    }

    return null;
}
