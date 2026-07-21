import type { DocumentProcessingStatus } from '@/features/document-requests/types';

/**
 * English keys for OCR processing statuses. Pass through `t()` for display.
 */
export const DOCUMENT_PROCESSING_STATUS_LABELS: Record<
    DocumentProcessingStatus,
    string
> = {
    pending: 'Pending',
    processing: 'Processing',
    completed: 'Completed',
    failed: 'Failed',
};

/**
 * Resolve a translated label for an uploaded document processing status.
 */
export function documentProcessingStatusLabel(
    status: DocumentProcessingStatus,
    t: (key: string) => string,
): string {
    return t(DOCUMENT_PROCESSING_STATUS_LABELS[status]);
}
