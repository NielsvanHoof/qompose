import {
    PortalBooleanDocumentAnswer,
    PortalTextDocumentAnswer,
} from '@/features/document-requests/portal/portal-document-answer';
import PortalDocumentUpload from '@/features/document-requests/portal/portal-document-upload';
import type { PortalDocumentRequest } from '@/features/document-requests/types';
import { storesAnswerText } from '@/features/document-requests/types';
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
    const { t, locale } = useTranslation();

    return (
        <div className="space-y-3">
            {documentRequest.uploaded_document ? (
                <div className="rounded-lg border border-dashed border-border/70 bg-muted/30 px-3 py-2.5 text-sm">
                    <p className="font-medium">
                        {documentRequest.uploaded_document.original_filename}
                    </p>
                    <p className="text-xs text-muted-foreground">
                        {formatBytes(
                            documentRequest.uploaded_document.size_bytes,
                        )}
                        {documentRequest.uploaded_document.uploaded_at ? (
                            <>
                                {' '}
                                ·{' '}
                                {new Date(
                                    documentRequest.uploaded_document
                                        .uploaded_at,
                                ).toLocaleString(locale)}
                            </>
                        ) : null}
                    </p>
                    {!canRespond ? (
                        <p className="mt-1 text-xs text-muted-foreground">
                            {t('File received.')}
                        </p>
                    ) : null}
                </div>
            ) : null}
            {canRespond ? (
                <PortalDocumentUpload documentRequest={documentRequest} />
            ) : null}
        </div>
    );
}

export function PortalTextRequestContent({
    documentRequest,
    canRespond,
}: PortalDocumentRequestTypeProps) {
    if (canRespond) {
        return <PortalTextDocumentAnswer documentRequest={documentRequest} />;
    }

    return <PortalSubmittedAnswerPreview documentRequest={documentRequest} />;
}

export function PortalBooleanRequestContent({
    documentRequest,
    canRespond,
}: PortalDocumentRequestTypeProps) {
    if (canRespond) {
        return (
            <PortalBooleanDocumentAnswer documentRequest={documentRequest} />
        );
    }

    return <PortalSubmittedAnswerPreview documentRequest={documentRequest} />;
}

/** Read-only answer when the client can no longer edit this item. */
function PortalSubmittedAnswerPreview({
    documentRequest,
}: {
    documentRequest: PortalDocumentRequest;
}) {
    const { t } = useTranslation();

    if (storesAnswerText(documentRequest.type)) {
        if (!documentRequest.answer_text) {
            return null;
        }

        return (
            <div className="rounded-lg bg-muted/50 px-3 py-2.5 text-sm">
                <p className="text-xs text-muted-foreground">{t('Your answer')}</p>
                <p className="mt-0.5 font-medium whitespace-pre-wrap">
                    {documentRequest.answer_text}
                </p>
            </div>
        );
    }

    if (
        documentRequest.type === 'boolean' &&
        documentRequest.answer_boolean !== null
    ) {
        return (
            <div className="rounded-lg bg-muted/50 px-3 py-2.5 text-sm">
                <p className="text-xs text-muted-foreground">{t('Your answer')}</p>
                <p className="mt-0.5 font-medium">
                    {documentRequest.answer_boolean ? t('Yes') : t('No')}
                </p>
            </div>
        );
    }

    return null;
}
