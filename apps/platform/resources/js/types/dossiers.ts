import type { AccessGrant } from './portal';

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
