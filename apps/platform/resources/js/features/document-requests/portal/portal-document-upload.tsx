import ClientPortalUploadController from '@/actions/App/Http/Controllers/Portal/ClientPortalUploadController';
import DocumentFileUploadForm from '@/features/document-requests/document-file-upload-form';
import type { PortalDocumentRequest } from '@/features/document-requests/types';
import { inlinePortalActionOptions } from '@/lib/inline-portal-action-options';

/**
 * Client-portal file upload for a single document request.
 * Uses the restricted client portal session, not staff auth.
 */
export default function PortalDocumentUpload({
    documentRequest,
}: {
    documentRequest: PortalDocumentRequest;
}) {
    return (
        <DocumentFileUploadForm
            inputId={`document-${documentRequest.id}`}
            hasExistingUpload={documentRequest.uploaded_document !== null}
            actionUrl={ClientPortalUploadController.store.url({
                documentRequest: documentRequest.id,
            })}
            visitOptions={inlinePortalActionOptions}
            className="mt-3 space-y-2"
            progressClassName="h-1.5 w-full accent-primary"
        />
    );
}
