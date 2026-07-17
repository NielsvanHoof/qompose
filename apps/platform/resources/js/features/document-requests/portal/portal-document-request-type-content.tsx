import {
    PortalBooleanDocumentAnswer,
    PortalTextDocumentAnswer,
} from '@/features/document-requests/portal/portal-document-answer';
import PortalDocumentUpload from '@/features/document-requests/portal/portal-document-upload';
import type { PortalDocumentRequest } from '@/features/document-requests/types';
import { formatBytes } from '@/lib/format-bytes';

export type PortalDocumentRequestTypeProps = {
    token: string;
    documentRequest: PortalDocumentRequest;
    canRespond: boolean;
};

/**
 * File requests show the current upload before offering a replacement.
 */
export function PortalFileRequestContent({
    token,
    documentRequest,
    canRespond,
}: PortalDocumentRequestTypeProps) {
    return (
        <>
            {documentRequest.uploaded_document && (
                <div className="rounded-md bg-muted/50 px-3 py-2 text-sm">
                    <p className="font-medium">
                        {documentRequest.uploaded_document.original_filename}
                    </p>
                    <p className="text-muted-foreground">
                        {formatBytes(
                            documentRequest.uploaded_document.size_bytes,
                        )}
                        {documentRequest.uploaded_document.uploaded_at && (
                            <>
                                {' '}
                                ·{' '}
                                {new Date(
                                    documentRequest.uploaded_document
                                        .uploaded_at,
                                ).toLocaleString()}
                            </>
                        )}
                    </p>
                </div>
            )}
            {canRespond && (
                <PortalDocumentUpload
                    token={token}
                    documentRequest={documentRequest}
                />
            )}
        </>
    );
}

export function PortalTextRequestContent({
    token,
    documentRequest,
    canRespond,
}: PortalDocumentRequestTypeProps) {
    if (!canRespond) {
        return null;
    }

    return (
        <PortalTextDocumentAnswer
            token={token}
            documentRequest={documentRequest}
        />
    );
}

export function PortalBooleanRequestContent({
    token,
    documentRequest,
    canRespond,
}: PortalDocumentRequestTypeProps) {
    if (!canRespond) {
        return null;
    }

    return (
        <PortalBooleanDocumentAnswer
            token={token}
            documentRequest={documentRequest}
        />
    );
}
