import type { DossierStatus } from '@/features/dossiers/types';

/**
 * English keys for dossier statuses. Pass through `t()` for display.
 */
export const DOSSIER_STATUS_LABELS: Record<DossierStatus, string> = {
    draft: 'Draft',
    awaiting_client: 'Awaiting client',
    in_review: 'In review',
    completed: 'Completed',
};

export type DossierStatusBadgeVariant =
    'default' | 'secondary' | 'destructive' | 'outline';

/**
 * Resolve a translated label for a dossier status.
 */
export function dossierStatusLabel(
    status: DossierStatus,
    t: (key: string) => string,
): string {
    return t(DOSSIER_STATUS_LABELS[status]);
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
