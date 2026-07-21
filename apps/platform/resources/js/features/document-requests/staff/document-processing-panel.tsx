import { Link } from '@inertiajs/react';
import UploadedDocumentController from '@/actions/App/Http/Controllers/Dossiers/UploadedDocumentController';
import ErrorState from '@/components/error-state';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { documentProcessingStatusLabel } from '@/features/document-requests/status/document-processing-status';
import type {
    DocumentProcessingStatus,
    UploadedDocument,
} from '@/features/document-requests/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import { formatBytes } from '@/lib/format-bytes';
import { formatDateTime } from '@/lib/format-date-time';

/**
 * Compact file strip on the dossier: status + download + link to extraction page.
 */
export default function DocumentProcessingPanel({
    uploadedDocument,
    canDownload,
}: {
    uploadedDocument: UploadedDocument;
    canDownload: boolean;
}) {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();
    const isComplete = uploadedDocument.processing_status === 'completed';

    return (
        <div className="space-y-3 rounded-md bg-muted/50 px-3 py-2 text-sm">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <div className="min-w-0 space-y-1">
                    <div className="flex flex-wrap items-center gap-2">
                        <p className="truncate font-medium">
                            {uploadedDocument.original_filename}
                        </p>
                        <Badge
                            variant={processingBadgeVariant(
                                uploadedDocument.processing_status,
                            )}
                        >
                            {t('OCR :status', {
                                status: documentProcessingStatusLabel(
                                    uploadedDocument.processing_status,
                                    t,
                                ),
                            })}
                        </Badge>
                    </div>
                    <p className="text-muted-foreground">
                        {formatBytes(uploadedDocument.size_bytes)} ·{' '}
                        {formatDateTime(uploadedDocument.uploaded_at)}
                    </p>
                </div>
                <div className="flex flex-wrap gap-2">
                    {isComplete && (
                        <Button variant="secondary" size="sm" asChild>
                            <Link
                                href={UploadedDocumentController.show.url({
                                    tenant: currentWorkspace,
                                    uploadedDocument: uploadedDocument.id,
                                })}
                            >
                                {t('View extraction')}
                            </Link>
                        </Button>
                    )}
                    {canDownload && (
                        <Button variant="outline" size="sm" asChild>
                            <a
                                href={UploadedDocumentController.download.url({
                                    tenant: currentWorkspace,
                                    uploadedDocument: uploadedDocument.id,
                                })}
                            >
                                {t('Download')}
                            </a>
                        </Button>
                    )}
                </div>
            </div>

            {uploadedDocument.processing_status === 'failed' &&
                uploadedDocument.processing_error && (
                    <ErrorState
                        variant="inline"
                        title={t('Processing failed')}
                        description={uploadedDocument.processing_error}
                    />
                )}

            {(uploadedDocument.processing_status === 'pending' ||
                uploadedDocument.processing_status === 'processing') && (
                <p className="text-muted-foreground">
                    {t('Analyzing document structure (forms and tables)…')}
                </p>
            )}
        </div>
    );
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
