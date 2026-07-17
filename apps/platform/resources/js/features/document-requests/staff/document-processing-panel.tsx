import UploadedDocumentController from '@/actions/App/Http/Controllers/Dossiers/UploadedDocumentController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type {
    DocumentProcessingStatus,
    UploadedDocument,
} from '@/features/document-requests/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { formatBytes } from '@/lib/format-bytes';
import { formatDateTime } from '@/lib/format-date-time';

/**
 * Staff view of an uploaded file: metadata, OCR status, and extracted text.
 */
export default function DocumentProcessingPanel({
    uploadedDocument,
    canDownload,
}: {
    uploadedDocument: UploadedDocument;
    canDownload: boolean;
}) {
    const currentWorkspace = useCurrentWorkspace();

    return (
        <div className="space-y-3 rounded-md bg-muted/50 px-3 py-2 text-sm">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <div className="space-y-1">
                    <div className="flex flex-wrap items-center gap-2">
                        <p className="font-medium">
                            {uploadedDocument.original_filename}
                        </p>
                        <Badge
                            variant={processingBadgeVariant(
                                uploadedDocument.processing_status,
                            )}
                        >
                            OCR{' '}
                            {processingLabel(
                                uploadedDocument.processing_status,
                            )}
                        </Badge>
                    </div>
                    <p className="text-muted-foreground">
                        {formatBytes(uploadedDocument.size_bytes)} ·{' '}
                        {formatDateTime(uploadedDocument.uploaded_at)}
                    </p>
                </div>
                {canDownload && (
                    <Button variant="outline" size="sm" asChild>
                        <a
                            href={UploadedDocumentController.download.url({
                                tenant: currentWorkspace,
                                uploadedDocument: uploadedDocument.id,
                            })}
                        >
                            Download
                        </a>
                    </Button>
                )}
            </div>

            {uploadedDocument.processing_status === 'failed' &&
                uploadedDocument.processing_error && (
                    <p className="text-destructive">
                        Processing failed: {uploadedDocument.processing_error}
                    </p>
                )}

            {(uploadedDocument.processing_status === 'pending' ||
                uploadedDocument.processing_status === 'processing') && (
                <p className="text-muted-foreground">
                    Analyzing document structure (forms and tables)…
                </p>
            )}

            {uploadedDocument.processing_status === 'completed' &&
                uploadedDocument.extracted_text && (
                    <div className="space-y-1">
                        <p className="font-medium text-muted-foreground">
                            Extracted JSON
                        </p>
                        <pre className="max-h-64 overflow-auto rounded-md border bg-background px-3 py-2 font-mono text-xs whitespace-pre-wrap">
                            {formatExtractedPayload(
                                uploadedDocument.extracted_text,
                            )}
                        </pre>
                    </div>
                )}
        </div>
    );
}

/** Pretty-print JSON when OCR returned AnalyzeDocument output. */
function formatExtractedPayload(raw: string): string {
    try {
        return JSON.stringify(JSON.parse(raw), null, 2);
    } catch {
        return raw;
    }
}

function processingLabel(status: DocumentProcessingStatus): string {
    return status.replaceAll('_', ' ');
}

function processingBadgeVariant(
    status: DocumentProcessingStatus,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'completed':
            return 'default';
        case 'failed':
            return 'destructive';
        case 'processing':
            return 'secondary';
        default:
            return 'outline';
    }
}
