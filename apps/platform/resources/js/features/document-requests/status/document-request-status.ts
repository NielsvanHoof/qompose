import type { DocumentRequestStatus } from '@/features/document-requests/types';

/**
 * English keys for document request statuses. Pass through `t()` for display.
 * Accepted maps to "Approved" to match review/portal product language.
 */
export const DOCUMENT_REQUEST_STATUS_LABELS: Record<
    DocumentRequestStatus,
    string
> = {
    pending: 'Pending',
    submitted: 'Submitted',
    accepted: 'Approved',
    rejected: 'Rejected',
};

/**
 * Resolve a translated label for a document request status.
 */
export function documentRequestStatusLabel(
    status: DocumentRequestStatus,
    t: (key: string) => string,
): string {
    return t(DOCUMENT_REQUEST_STATUS_LABELS[status]);
}
