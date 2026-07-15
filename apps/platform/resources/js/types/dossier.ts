/** Uploaded file on a staff dossier document request. */
export type UploadedDocument = {
    id: number;
    original_filename: string;
    size_bytes: number;
    uploaded_at: string;
};

/** Document request as shown on the staff dossier page. */
export type DocumentRequest = {
    id: number;
    type: string;
    title: string;
    instructions: string | null;
    status: string;
    answer_text: string | null;
    answer_boolean: boolean | null;
    answered_at: string | null;
    sort_order: number;
    uploaded_document: UploadedDocument | null;
};

/** Magic-link grant for client portal access. */
export type AccessGrant = {
    id: number;
    expires_at: string;
    revoked_at: string | null;
    last_used_at: string | null;
    is_valid: boolean;
};

/** Full dossier payload for the staff show page. */
export type Dossier = {
    id: number;
    title: string;
    reference: string | null;
    status: string;
    client: {
        name: string;
        email: string;
    };
    document_requests: DocumentRequest[];
    access_grants: AccessGrant[];
};

/** Compact dossier row for index / dashboard lists. */
export type DossierSummary = {
    id: number;
    title: string;
    reference: string | null;
    status: string;
    client_name: string;
    updated_at?: string;
};

/** Client option for the create-dossier form. */
export type DossierClientOption = {
    id: number;
    name: string;
    email: string;
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
    status: string;
    answer_text: string | null;
    answer_boolean: boolean | null;
    uploaded_document: PortalUploadedDocument | null;
};

/** Dossier payload for the client portal page. */
export type PortalDossier = {
    title: string;
    reference: string | null;
    client: {
        name: string;
    };
    expires_at: string | null;
    document_requests: PortalDocumentRequest[];
};
