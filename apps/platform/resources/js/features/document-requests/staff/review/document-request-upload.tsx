import UploadedDocumentController from '@/actions/App/Http/Controllers/Dossiers/UploadedDocumentController';
import DocumentFileUploadForm from '@/features/document-requests/document-file-upload-form';
import type { DocumentRequest } from '@/features/document-requests/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { inlineDossierActionOptions } from '@/lib/inline-dossier-action-options';

/**
 * Staff fallback file upload (email / walk-in).
 * Shown only after expanding “Upload/Replace on behalf of client”.
 */
export default function DocumentRequestUpload({
    dossierId,
    documentRequest,
}: {
    dossierId: number;
    documentRequest: DocumentRequest;
}) {
    const currentWorkspace = useCurrentWorkspace();

    return (
        <DocumentFileUploadForm
            inputId={`document-${documentRequest.id}`}
            hasExistingUpload={documentRequest.uploaded_document !== null}
            actionUrl={UploadedDocumentController.store.url({
                tenant: currentWorkspace,
                dossier: dossierId,
                documentRequest: documentRequest.id,
            })}
            visitOptions={inlineDossierActionOptions}
        />
    );
}
