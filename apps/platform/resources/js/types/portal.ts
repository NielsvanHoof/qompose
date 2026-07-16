import type { DocumentRequestStatus, DossierStatus } from './dossiers';

/** Magic-link grant for client portal access. */
export type AccessGrant = {
    id: number;
    expires_at: string;
    revoked_at: string | null;
    last_used_at: string | null;
    is_valid: boolean;
};

/** Uploaded file as shown on the client portal (no download id). */
export type PortalUploadedDocument = {
    original_filename: string;
    size_bytes: number;
    uploaded_at: string | null;
};

/** Document request as shown on the client portal. */
export type PortalDocumentRequest = {
    id: number;
    type: string;
    title: string;
    instructions: string | null;
    status: DocumentRequestStatus;
    answer_text: string | null;
    answer_boolean: boolean | null;
    rejection_reason: string | null;
    uploaded_document: PortalUploadedDocument | null;
};

/** Dossier payload for the client portal page. */
export type PortalDossier = {
    title: string;
    reference: string | null;
    status: DossierStatus;
    client: {
        name: string;
    };
    expires_at: string | null;
    document_requests: PortalDocumentRequest[];
};
