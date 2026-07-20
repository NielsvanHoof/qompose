import type { DossierStatus } from '@/features/dossiers/types';

export const DOSSIER_STATUS_LABELS: Record<DossierStatus, string> = {
    draft: 'Draft',
    awaiting_client: 'Awaiting client',
    in_review: 'In review',
    completed: 'Completed',
};

export type DossierStatusBadgeVariant =
    'default' | 'secondary' | 'destructive' | 'outline';

export function dossierStatusLabel(status: DossierStatus): string {
    return DOSSIER_STATUS_LABELS[status];
}

export function dossierStatusBadgeVariant(
    status: DossierStatus,
): DossierStatusBadgeVariant {
    switch (status) {
        case 'completed':
            return 'default';
        case 'in_review':
            return 'secondary';
        case 'awaiting_client':
            return 'outline';
        default:
            return 'secondary';
    }
}
