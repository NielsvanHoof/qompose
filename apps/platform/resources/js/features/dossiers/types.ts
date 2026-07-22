import type { DocumentRequest } from '@/features/document-requests/types';
import type { AccessGrant } from '../portal/types';

export type DossierStatus =
    'draft' | 'awaiting_client' | 'in_review' | 'completed';

/** Full dossier payload for the staff show page. */
export type Dossier = {
    id: number;
    title: string;
    reference: string | null;
    status: DossierStatus;
    due_date: string | null;
    responsible_staff: ResponsibleStaffOption | null;
    reminder_interval_days: number | null;
    next_reminder_at: string | null;
    last_client_message_sent_at: string | null;
    last_client_opened_at: string | null;
    has_outstanding_client_items: boolean;
    ready_to_complete: boolean;
    review_summary: {
        total: number;
        pending: number;
        submitted: number;
        accepted: number;
        rejected: number;
    };
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
    status: DossierStatus;
    client_name: string;
    due_date?: string | null;
    responsible_name?: string | null;
    updated_at?: string;
};

export type ArchivedDossierSummary = DossierSummary & {
    client_archived: boolean;
    archived_at: string;
};

export type EditableDossier = Pick<
    Dossier,
    | 'id'
    | 'title'
    | 'reference'
    | 'client'
    | 'due_date'
    | 'reminder_interval_days'
>;

export type EditableDossierWithResponsibility = EditableDossier & {
    responsible_user_id: number | null;
};

/** Client option for the create-dossier form. */
export type DossierClientOption = {
    id: number;
    name: string;
    email: string;
};

export type ResponsibleStaffOption = {
    id: number;
    name: string;
    email: string;
};
