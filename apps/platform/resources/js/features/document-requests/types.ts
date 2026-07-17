/**
 * Frontend mirror of the backend QuestionnaireItemType enum.
 * Keeping this union explicit makes every type-specific UI exhaustive.
 */
export type QuestionnaireItemType = 'file' | 'text' | 'boolean';

export type DocumentRequestStatus =
    'pending' | 'submitted' | 'accepted' | 'rejected';

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
    type: QuestionnaireItemType;
    title: string;
    instructions: string | null;
    status: DocumentRequestStatus;
    answer_text: string | null;
    answer_boolean: boolean | null;
    answered_at: string | null;
    reviewed_at: string | null;
    reviewed_by_name: string | null;
    rejection_reason: string | null;
    sort_order: number;
    uploaded_document: UploadedDocument | null;
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
    type: QuestionnaireItemType;
    title: string;
    instructions: string | null;
    status: DocumentRequestStatus;
    answer_text: string | null;
    answer_boolean: boolean | null;
    rejection_reason: string | null;
    uploaded_document: PortalUploadedDocument | null;
};
