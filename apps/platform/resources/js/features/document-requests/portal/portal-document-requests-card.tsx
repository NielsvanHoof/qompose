import EmptyState from '@/components/empty-state';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { documentRequestStatusLabel } from '@/features/document-requests/document-request-status';
import { getQuestionnaireItemTypeDefinition } from '@/features/document-requests/questionnaire-item-type-registry';
import type { PortalDocumentRequest } from '@/features/document-requests/types';
import { useTranslation } from '@/hooks/use-translation';

/**
 * Client-portal questionnaire with uploads and typed answers.
 */
export default function PortalDocumentRequestsCard({
    firmName,
    documentRequests,
}: {
    firmName: string;
    documentRequests: PortalDocumentRequest[];
}) {
    const { t } = useTranslation();
    const submittedCount = documentRequests.filter(
        (item) => item.status === 'submitted' || item.status === 'accepted',
    ).length;
    const approvedCount = documentRequests.filter(
        (item) => item.status === 'accepted',
    ).length;

    return (
        <Card className="border-primary/10 shadow-sm">
            <CardHeader>
                <CardTitle>{t('Questionnaire')}</CardTitle>
                <CardDescription>
                    {t(
                        'Complete each item below. Submitted items are locked while they are reviewed; if changes are requested, correct and resubmit them.',
                    )}
                    {documentRequests.length > 0 && (
                        <>
                            {' '}
                            {t(
                                'Progress: :submitted / :total answered · :approved approved',
                                {
                                    submitted: submittedCount,
                                    total: documentRequests.length,
                                    approved: approvedCount,
                                },
                            )}
                        </>
                    )}
                </CardDescription>
            </CardHeader>
            <CardContent>
                {documentRequests.length === 0 ? (
                    <EmptyState
                        title={t(
                            'Nothing has been requested yet. Check back later or contact :firm.',
                            { firm: firmName },
                        )}
                    />
                ) : (
                    <div className="divide-y rounded-md border">
                        {documentRequests.map((documentRequest) => (
                            <div
                                key={documentRequest.id}
                                className="space-y-2 px-4 py-4"
                            >
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="font-medium">
                                                {documentRequest.title}
                                            </p>
                                            <Badge variant="outline">
                                                {t(
                                                    getQuestionnaireItemTypeDefinition(
                                                        documentRequest.type,
                                                    ).label,
                                                )}
                                            </Badge>
                                        </div>
                                        {documentRequest.instructions && (
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {documentRequest.instructions}
                                            </p>
                                        )}
                                    </div>
                                    <Badge variant="outline">
                                        {documentRequestStatusLabel(
                                            documentRequest.status,
                                            t,
                                        )}
                                    </Badge>
                                </div>

                                {documentRequest.status === 'rejected' &&
                                    documentRequest.rejection_reason && (
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
                                    )}

                                <PortalRequestTypeContent
                                    documentRequest={documentRequest}
                                    canRespond={documentRequest.can_respond}
                                />

                                {!documentRequest.can_respond &&
                                documentRequest.status === 'submitted' ? (
                                    <p className="text-sm text-muted-foreground">
                                        {t('Submitted and waiting for review.')}
                                    </p>
                                ) : documentRequest.status === 'accepted' ? (
                                    <p className="text-sm text-success-foreground">
                                        {t('Approved by :firm.', {
                                            firm: firmName,
                                        })}
                                    </p>
                                ) : null}
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
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
