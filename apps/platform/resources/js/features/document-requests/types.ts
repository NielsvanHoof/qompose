/**
 * Frontend mirror of the backend QuestionnaireItemType enum.
 * Keeping this union explicit makes every type-specific UI exhaustive.
 */
export type QuestionnaireItemType = 'file' | 'text' | 'boolean';

export type DocumentRequestStatus =
    'pending' | 'submitted' | 'accepted' | 'rejected';

/** Parallel OCR lifecycle on the uploaded file (independent of review status). */
export type DocumentProcessingStatus =
    'pending' | 'processing' | 'completed' | 'failed';

/** Uploaded file on a staff dossier document request (list payload — no OCR body). */
export type UploadedDocument = {
    id: number;
    original_filename: string;
    size_bytes: number;
    uploaded_at: string;
    processing_status: DocumentProcessingStatus;
    processing_error: string | null;
};

/** Parsed Bedrock-structured OCR payload on the extraction page. */
export type ExtractionFieldSensitivity =
    | 'bsn'
    | 'iban'
    | 'id_number'
    | 'account_number'
    | 'date_of_birth'
    | 'email'
    | 'phone';

export type DocumentExtractionField = {
    label: string;
    value: string | string[];
    /** Textract LINE match score 0–1, or null when unmatched / legacy. */
    confidence: number | null;
    /** PII category for UI masking, or null when not sensitive. */
    sensitivity: ExtractionFieldSensitivity | null;
};

export type DocumentExtractionTable = {
    title: string | null;
    headers: string[];
    rows: string[][];
};

export type DocumentExtraction = {
    document_type: string | null;
    summary: string | null;
    fields: DocumentExtractionField[];
    tables: DocumentExtractionTable[];
    notes: string[];
    /** Average Textract LINE confidence 0–1, or null when unknown. */
    confidence: number | null;
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
    can_respond: boolean;
    uploaded_document: PortalUploadedDocument | null;
};
