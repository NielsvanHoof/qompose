import PortalDocumentUpload from '@/components/dossiers/portal-document-upload';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatBytes } from '@/lib/format-bytes';
import type { PortalDocumentRequest } from '@/types';

/**
 * Client-portal list of requested documents with upload controls.
 */
export default function PortalDocumentRequestsCard({
    token,
    firmName,
    documentRequests,
}: {
    token: string;
    firmName: string;
    documentRequests: PortalDocumentRequest[];
}) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Requested documents</CardTitle>
                <CardDescription>
                    Upload each file below. You can replace a file until this
                    link expires.
                </CardDescription>
            </CardHeader>
            <CardContent>
                {documentRequests.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No documents have been requested yet. Check back later
                        or contact {firmName}.
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
                                        <p className="font-medium">
                                            {documentRequest.title}
                                        </p>
                                        {documentRequest.instructions && (
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {documentRequest.instructions}
                                            </p>
                                        )}
                                    </div>
                                    <Badge variant="outline">
                                        {documentRequest.status.replaceAll(
                                            '_',
                                            ' ',
                                        )}
                                    </Badge>
                                </div>

                                {documentRequest.uploaded_document && (
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
                                            {documentRequest.uploaded_document
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

                                <PortalDocumentUpload
                                    token={token}
                                    documentRequest={documentRequest}
                                />
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
