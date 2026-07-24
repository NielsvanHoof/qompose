import { Link, usePoll } from '@inertiajs/react';
import { useEffect } from 'react';
import UploadedDocumentController from '@/actions/App/Http/Controllers/Dossiers/UploadedDocumentController';
import ErrorState from '@/components/error-state';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import DocumentExtractionView from '@/features/document-requests/staff/review/document-extraction-view';
import type { UploadedDocumentShowProps } from '@/features/document-requests/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import { formatBytes } from '@/lib/format-bytes';
import { formatDateTime } from '@/lib/format-date-time';
import { show as showDossier } from '@/routes/workspaces/dossiers';

/**
 * OCR extraction body for a single uploaded document.
 * Polls while processing so results appear without a full page reload.
 */
export default function UploadedDocumentShowContent({
    uploaded_document: uploadedDocument,
    document_request: documentRequest,
    dossier,
    can_download: canDownload,
}: UploadedDocumentShowProps) {
    const currentWorkspace = useCurrentWorkspace();
    const { t, locale } = useTranslation();

    const isProcessing =
        uploadedDocument.processing_status === 'pending' ||
        uploadedDocument.processing_status === 'processing';

    const { start, stop } = usePoll(
        2000,
        {
            only: ['uploaded_document'],
        },
        {
            autoStart: false,
            mode: 'rest',
        },
    );

    useEffect(() => {
        if (isProcessing) {
            start();
        } else {
            stop();
        }
    }, [isProcessing, start, stop]);

    return (
        <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <Heading
                    level={1}
                    title={uploadedDocument.original_filename}
                    description={
                        documentRequest
                            ? t('Extraction for “:title”', {
                                  title: documentRequest.title,
                              })
                            : t('Document extraction')
                    }
                />
                <div className="flex flex-wrap items-center gap-2">
                    <Badge variant="secondary">
                        OCR{' '}
                        {uploadedDocument.processing_status.replaceAll(
                            '_',
                            ' ',
                        )}
                    </Badge>
                    {dossier && (
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={showDossier({
                                    tenant: currentWorkspace,
                                    dossier: dossier.id,
                                })}
                            >
                                {t('Back to dossier')}
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

            <p className="font-data text-sm text-muted-foreground">
                {formatBytes(uploadedDocument.size_bytes)} · {t('uploaded')}{' '}
                {formatDateTime(uploadedDocument.uploaded_at, locale)}
            </p>

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
                <p className="text-sm text-muted-foreground">
                    {t(
                        'Analysis is still running. Results will appear automatically.',
                    )}
                </p>
            )}

            <DocumentExtractionView
                extraction={uploadedDocument.extraction}
                rawJson={uploadedDocument.raw_json}
            />
        </div>
    );
}
