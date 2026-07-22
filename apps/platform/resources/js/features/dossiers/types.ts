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
    updated_at?: string;
};

export type ArchivedDossierSummary = DossierSummary & {
    client_archived: boolean;
    archived_at: string;
};

export type EditableDossier = Pick<
    Dossier,
    'id' | 'title' | 'reference' | 'client'
>;

/** Client option for the create-dossier form. */
export type DossierClientOption = {
    id: number;
    name: string;
    email: string;
};
