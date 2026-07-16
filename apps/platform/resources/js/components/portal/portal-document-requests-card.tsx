import PortalDocumentAnswer from '@/components/portal/portal-document-answer';
import PortalDocumentUpload from '@/components/portal/portal-document-upload';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatBytes } from '@/lib/format-bytes';
import type {
    DocumentRequestStatus,
    DossierStatus,
    PortalDocumentRequest,
} from '@/types';

/**
 * Client-portal questionnaire with uploads and typed answers.
 */
export default function PortalDocumentRequestsCard({
    token,
    firmName,
    dossierStatus,
    documentRequests,
}: {
    token: string;
    firmName: string;
    dossierStatus: DossierStatus;
    documentRequests: PortalDocumentRequest[];
}) {
    const submittedCount = documentRequests.filter(
        (item) => item.status === 'submitted' || item.status === 'accepted',
    ).length;
    const approvedCount = documentRequests.filter(
        (item) => item.status === 'accepted',
    ).length;

    return (
        <Card>
            <CardHeader>
                <CardTitle>Questionnaire</CardTitle>
                <CardDescription>
                    Complete each item below. Submitted items are locked while
                    they are reviewed; if changes are requested, correct and
                    resubmit them.
                    {documentRequests.length > 0 && (
                        <>
                            {' '}
                            Progress: {submittedCount} /{' '}
                            {documentRequests.length} answered · {approvedCount}{' '}
                            approved
                        </>
                    )}
                </CardDescription>
            </CardHeader>
            <CardContent>
                {documentRequests.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Nothing has been requested yet. Check back later or
                        contact {firmName}.
                    </p>
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
                                                {documentRequest.type}
                                            </Badge>
                                        </div>
                                        {documentRequest.instructions && (
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {documentRequest.instructions}
                                            </p>
                                        )}
                                    </div>
                                    <Badge variant="outline">
                                        {statusLabel(documentRequest.status)}
                                    </Badge>
                                </div>

                                {documentRequest.status === 'rejected' &&
                                    documentRequest.rejection_reason && (
                                        <Alert variant="destructive">
                                            <AlertTitle>
                                                Changes requested
                                            </AlertTitle>
                                            <AlertDescription>
                                                {
                                                    documentRequest.rejection_reason
                                                }
                                            </AlertDescription>
                                        </Alert>
                                    )}

                                {documentRequest.type === 'file' &&
                                    documentRequest.uploaded_document && (
                                        <div className="rounded-md bg-muted/50 px-3 py-2 text-sm">
                                            <p className="font-medium">
                                                {
                                                    documentRequest
                                                        .uploaded_document
                                                        .original_filename
                                                }
                                            </p>
                                            <p className="text-muted-foreground">
                                                {formatBytes(
                                                    documentRequest
                                                        .uploaded_document
                                                        .size_bytes,
                                                )}
                                                {documentRequest
                                                    .uploaded_document
                                                    .uploaded_at && (
                                                    <>
                                                        {' '}
                                                        ·{' '}
                                                        {new Date(
                                                            documentRequest
                                                                .uploaded_document
                                                                .uploaded_at,
                                                        ).toLocaleString()}
                                                    </>
                                                )}
                                            </p>
                                        </div>
                                    )}

                                {dossierStatus !== 'completed' &&
                                (documentRequest.status === 'pending' ||
                                    documentRequest.status === 'rejected') ? (
                                    documentRequest.type === 'file' ? (
                                        <PortalDocumentUpload
                                            token={token}
                                            documentRequest={documentRequest}
                                        />
                                    ) : (
                                        <PortalDocumentAnswer
                                            token={token}
                                            documentRequest={documentRequest}
                                        />
                                    )
                                ) : documentRequest.status === 'submitted' ? (
                                    <p className="text-sm text-muted-foreground">
                                        Submitted and waiting for review.
                                    </p>
                                ) : documentRequest.status === 'accepted' ? (
                                    <p className="text-sm text-emerald-700 dark:text-emerald-300">
                                        Approved by {firmName}.
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

function statusLabel(status: DocumentRequestStatus): string {
    return status === 'accepted' ? 'approved' : status.replaceAll('_', ' ');
}
