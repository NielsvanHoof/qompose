import { Head, Link, setLayoutProps, usePoll } from '@inertiajs/react';
import { useEffect } from 'react';
import UploadedDocumentController from '@/actions/App/Http/Controllers/Dossiers/UploadedDocumentController';
import ErrorState from '@/components/error-state';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import DocumentExtractionView from '@/features/document-requests/staff/document-extraction-view';
import type {
    DocumentExtraction,
    DocumentProcessingStatus,
} from '@/features/document-requests/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { formatBytes } from '@/lib/format-bytes';
import { formatDateTime } from '@/lib/format-date-time';
import {
    index as dossierIndex,
    show as showDossier,
} from '@/routes/workspaces/dossiers';

type ExtractionPageProps = {
    uploaded_document: {
        id: number;
        original_filename: string;
        size_bytes: number;
        uploaded_at: string;
        processing_status: DocumentProcessingStatus;
        processing_error: string | null;
        extraction: DocumentExtraction | null;
        raw_json: string | null;
    };
    document_request: { id: number; title: string } | null;
    dossier: { id: number; title: string } | null;
    can_download: boolean;
};

/**
 * Dedicated OCR extraction page for a single uploaded document.
 */
export default function ShowUploadedDocument({
    uploaded_document: uploadedDocument,
    document_request: documentRequest,
    dossier,
    can_download: canDownload,
}: ExtractionPageProps) {
    const currentWorkspace = useCurrentWorkspace();

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

    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Dossiers',
                href: dossierIndex(currentWorkspace),
            },
            ...(dossier
                ? [
                      {
                          title: dossier.title,
                          href: showDossier({
                              tenant: currentWorkspace,
                              dossier: dossier.id,
                          }),
                      },
                  ]
                : []),
            {
                title: uploadedDocument.original_filename,
                href: UploadedDocumentController.show.url({
                    tenant: currentWorkspace,
                    uploadedDocument: uploadedDocument.id,
                }),
            },
        ],
    });

    return (
        <>
            <Head
                title={`Extraction · ${uploadedDocument.original_filename}`}
            />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={uploadedDocument.original_filename}
                        description={
                            documentRequest
                                ? `Extraction for “${documentRequest.title}”`
                                : 'Document extraction'
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
                                    Back to dossier
                                </Link>
                            </Button>
                        )}
                        {canDownload && (
                            <Button variant="outline" size="sm" asChild>
                                <a
                                    href={UploadedDocumentController.download.url(
                                        {
                                            tenant: currentWorkspace,
                                            uploadedDocument:
                                                uploadedDocument.id,
                                        },
                                    )}
                                >
                                    Download
                                </a>
                            </Button>
                        )}
                    </div>
                </div>

                <p className="text-sm text-muted-foreground">
                    {formatBytes(uploadedDocument.size_bytes)} · uploaded{' '}
                    {formatDateTime(uploadedDocument.uploaded_at)}
                </p>

                {uploadedDocument.processing_status === 'failed' &&
                    uploadedDocument.processing_error && (
                        <ErrorState
                            variant="inline"
                            title="Processing failed"
                            description={uploadedDocument.processing_error}
                        />
                    )}

                {(uploadedDocument.processing_status === 'pending' ||
                    uploadedDocument.processing_status === 'processing') && (
                    <p className="text-sm text-muted-foreground">
                        Analysis is still running. Results will appear
                        automatically.
                    </p>
                )}

                <DocumentExtractionView
                    extraction={uploadedDocument.extraction}
                    rawJson={uploadedDocument.raw_json}
                />
            </div>
        </>
    );
}
