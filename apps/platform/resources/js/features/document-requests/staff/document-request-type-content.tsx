import { type ReactNode, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import DocumentProcessingPanel from '@/features/document-requests/staff/document-processing-panel';
import DocumentRequestUpload from '@/features/document-requests/staff/document-request-upload';
import type { DocumentRequest } from '@/features/document-requests/types';
import type { DossierStatus } from '@/features/dossiers/types';
import { useTranslation } from '@/hooks/use-translation';

export type StaffDocumentRequestTypeProps = {
    dossierId: number;
    dossierStatus: DossierStatus;
    documentRequest: DocumentRequest;
    canEdit: boolean;
    canDownload: boolean;
};

/**
 * File requests: OCR/review first; staff upload is a collapsed fallback.
 */
export function StaffFileRequestContent({
    dossierId,
    dossierStatus,
    documentRequest,
    canEdit,
    canDownload,
}: StaffDocumentRequestTypeProps) {
    const hasUpload = documentRequest.uploaded_document !== null;

    return (
        <>
            {hasUpload && documentRequest.uploaded_document && (
                <DocumentProcessingPanel
                    uploadedDocument={documentRequest.uploaded_document}
                    canDownload={canDownload}
                />
            )}

            {!hasUpload && (
                <WaitingForClientUpload dossierStatus={dossierStatus} />
            )}

            {canEdit && (
                <StaffUploadOnBehalfFallback
                    dossierId={dossierId}
                    documentRequest={documentRequest}
                    hasUpload={hasUpload}
                />
            )}
        </>
    );
}

/**
 * Quiet empty state — clients should upload via the portal.
 */
function WaitingForClientUpload({
    dossierStatus,
}: {
    dossierStatus: DossierStatus;
}) {
    const { t } = useTranslation();

    return (
        <div className="rounded-md bg-muted/50 px-3 py-2 text-sm text-muted-foreground">
            <p>{t('Waiting for the client to upload via the portal.')}</p>
            {dossierStatus === 'draft' && (
                <p className="mt-1">
                    {t(
                        'Invite the client from Client Access so they can upload securely.',
                    )}
                </p>
            )}
        </div>
    );
}

/**
 * Staff upload stays available for email / walk-in, but is not the primary CTA.
 */
function StaffUploadOnBehalfFallback({
    dossierId,
    documentRequest,
    hasUpload,
}: {
    dossierId: number;
    documentRequest: DocumentRequest;
    hasUpload: boolean;
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const triggerLabel = hasUpload
        ? t('Replace on behalf of client')
        : t('Upload on behalf of client');

    return (
        <Collapsible open={open} onOpenChange={setOpen} className="mt-2">
            <CollapsibleTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    className="px-0"
                >
                    {open ? t('Hide staff upload') : triggerLabel}
                </Button>
            </CollapsibleTrigger>
            <CollapsibleContent>
                <DocumentRequestUpload
                    dossierId={dossierId}
                    documentRequest={documentRequest}
                />
            </CollapsibleContent>
        </Collapsible>
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
            {/* Answer text is user-generated — do not translate. */}
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
    const { t } = useTranslation();

    if (documentRequest.answer_boolean === null) {
        return null;
    }

    return (
        <AnswerPreview>
            <p className="font-medium">
                {documentRequest.answer_boolean ? t('Yes') : t('No')}
            </p>
        </AnswerPreview>
    );
}

function AnswerPreview({ children }: { children: ReactNode }) {
    const { t } = useTranslation();

    return (
        <div className="rounded-md bg-muted/50 px-3 py-2 text-sm">
            <p className="text-muted-foreground">{t('Answer')}</p>
            {children}
        </div>
    );
}
