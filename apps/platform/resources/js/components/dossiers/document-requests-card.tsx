import UploadedDocumentController from '@/actions/App/Http/Controllers/Workspace/UploadedDocumentController';
import DocumentRequestUpload from '@/components/dossiers/document-request-upload';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatBytes } from '@/lib/format-bytes';
import type { DocumentRequest } from '@/types';

/**
 * Staff list of document requests with download and upload controls.
 */
export default function DocumentRequestsCard({
    dossierId,
    documentRequests,
}: {
    dossierId: number;
    documentRequests: DocumentRequest[];
}) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Document requests</CardTitle>
                <CardDescription>
                    Upload files received from the client, or wait for portal
                    uploads.
                </CardDescription>
            </CardHeader>
            <CardContent>
                {documentRequests.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No documents requested yet.
                    </p>
                ) : (
                    <div className="divide-y rounded-md border">
                        {documentRequests.map((documentRequest) => (
                            <div
                                key={documentRequest.id}
                                className="space-y-2 px-4 py-3"
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
                                    <div className="flex flex-wrap items-center justify-between gap-2 rounded-md bg-muted/50 px-3 py-2 text-sm">
                                        <div>
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
                                                )}{' '}
                                                ·{' '}
                                                {new Date(
                                                    documentRequest
                                                        .uploaded_document
                                                        .uploaded_at,
                                                ).toLocaleString()}
                                            </p>
                                        </div>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <a
                                                href={UploadedDocumentController.download.url(
                                                    documentRequest
                                                        .uploaded_document.id,
                                                )}
                                            >
                                                Download
                                            </a>
                                        </Button>
                                    </div>
                                )}

                                <DocumentRequestUpload
                                    dossierId={dossierId}
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
