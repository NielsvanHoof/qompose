import type { PortalDocumentRequest } from '@/features/document-requests/types';
import type { DossierStatus } from '@/features/dossiers/types';

/** Magic-link grant for client portal access. */
export type AccessGrant = {
    id: number;
    expires_at: string;
    revoked_at: string | null;
    last_used_at: string | null;
    is_valid: boolean;
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
