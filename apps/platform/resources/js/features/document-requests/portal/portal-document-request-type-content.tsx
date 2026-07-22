import {
    PortalBooleanDocumentAnswer,
    PortalTextDocumentAnswer,
} from '@/features/document-requests/portal/portal-document-answer';
import PortalDocumentUpload from '@/features/document-requests/portal/portal-document-upload';
import type { PortalDocumentRequest } from '@/features/document-requests/types';
import { useTranslation } from '@/hooks/use-translation';
import { formatBytes } from '@/lib/format-bytes';

export type PortalDocumentRequestTypeProps = {
    documentRequest: PortalDocumentRequest;
    canRespond: boolean;
};

/**
 * File requests show the current upload before offering a replacement.
 */
export function PortalFileRequestContent({
    documentRequest,
    canRespond,
}: PortalDocumentRequestTypeProps) {
    const { locale } = useTranslation();

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
                                ).toLocaleString(locale)}
                            </>
                        )}
                    </p>
                </div>
            )}
            {canRespond && (
                <PortalDocumentUpload documentRequest={documentRequest} />
            )}
        </>
    );
}

export function PortalTextRequestContent({
    documentRequest,
    canRespond,
}: PortalDocumentRequestTypeProps) {
    if (!canRespond) {
        return null;
    }

    return <PortalTextDocumentAnswer documentRequest={documentRequest} />;
}

export function PortalBooleanRequestContent({
    documentRequest,
    canRespond,
}: PortalDocumentRequestTypeProps) {
    if (!canRespond) {
        return null;
    }

    return <PortalBooleanDocumentAnswer documentRequest={documentRequest} />;
}
