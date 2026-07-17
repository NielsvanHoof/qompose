import type { ReactNode } from 'react';
import UploadedDocumentController from '@/actions/App/Http/Controllers/Dossiers/UploadedDocumentController';
import { Button } from '@/components/ui/button';
import DocumentRequestUpload from '@/features/document-requests/staff/document-request-upload';
import type { DocumentRequest } from '@/features/document-requests/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { formatBytes } from '@/lib/format-bytes';
import { formatDateTime } from '@/lib/format-date-time';

export type StaffDocumentRequestTypeProps = {
    dossierId: number;
    documentRequest: DocumentRequest;
    canEdit: boolean;
    canDownload: boolean;
};

/**
 * File requests include their uploaded file and staff upload controls.
 */
export function StaffFileRequestContent({
    dossierId,
    documentRequest,
    canEdit,
    canDownload,
}: StaffDocumentRequestTypeProps) {
    const currentWorkspace = useCurrentWorkspace();

    return (
        <>
            {documentRequest.uploaded_document && (
                <div className="flex flex-wrap items-center justify-between gap-2 rounded-md bg-muted/50 px-3 py-2 text-sm">
                    <div>
                        <p className="font-medium">
                            {
                                documentRequest.uploaded_document
                                    .original_filename
                            }
                        </p>
                        <p className="text-muted-foreground">
                            {formatBytes(
                                documentRequest.uploaded_document.size_bytes,
                            )}{' '}
                            ·{' '}
                            {formatDateTime(
                                documentRequest.uploaded_document.uploaded_at,
                            )}
                        </p>
                    </div>
                    {canDownload && (
                        <Button variant="outline" size="sm" asChild>
                            <a
                                href={UploadedDocumentController.download.url({
                                    tenant: currentWorkspace,
                                    uploadedDocument:
                                        documentRequest.uploaded_document.id,
                                })}
                            >
                                Download
                            </a>
                        </Button>
                    )}
                </div>
            )}
            {canEdit && (
                <DocumentRequestUpload
                    dossierId={dossierId}
                    documentRequest={documentRequest}
                />
            )}
        </>
    );
}

/**
 * Text answers are rendered without knowing about other request types.
 */
export function StaffTextRequestContent({
    documentRequest,
}: StaffDocumentRequestTypeProps) {
    if (!documentRequest.answer_text) {
        return null;
    }

    return (
        <AnswerPreview>
            <p className="font-medium whitespace-pre-wrap">
                {documentRequest.answer_text}
            </p>
        </AnswerPreview>
    );
}

/**
 * Boolean answers preserve false as a submitted answer.
 */
export function StaffBooleanRequestContent({
    documentRequest,
}: StaffDocumentRequestTypeProps) {
    if (documentRequest.answer_boolean === null) {
        return null;
    }

    return (
        <AnswerPreview>
            <p className="font-medium">
                {documentRequest.answer_boolean ? 'Yes' : 'No'}
            </p>
        </AnswerPreview>
    );
}

function AnswerPreview({ children }: { children: ReactNode }) {
    return (
        <div className="rounded-md bg-muted/50 px-3 py-2 text-sm">
            <p className="text-muted-foreground">Answer</p>
            {children}
        </div>
    );
}
