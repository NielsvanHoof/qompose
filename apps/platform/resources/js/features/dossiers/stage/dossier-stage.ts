import type { Dossier } from '@/features/dossiers/types';

export const STAGE_ORDER = ['prepare', 'invite', 'review'] as const;

export type DossierWorkflowStage = (typeof STAGE_ORDER)[number];

/**
 * English keys for dossier stage labels. Pass through `t()` for display.
 */
export const STAGE_LABELS: Record<DossierWorkflowStage, string> = {
    prepare: 'Build Form',
    invite: 'Send Invite',
    review: 'Review',
};

/**
 * English keys for dossier stage hints. Pass through `t()` for display.
 */
export const STAGE_HINTS: Record<DossierWorkflowStage, string> = {
    prepare: 'Drag components onto the canvas to build the client form.',
    invite: 'Send a portal link so the client can complete the form. Then continue to Review.',
    review: 'Check submissions, approve or request changes, then complete the dossier.',
};

/**
 * Resolve a translated label for a dossier stage.
 */
export function dossierStageLabel(
    stage: DossierWorkflowStage,
    t: (key: string) => string,
): string {
    return t(STAGE_LABELS[stage]);
}

/**
 * Resolve a translated hint for a dossier stage.
 */
export function dossierStageHint(
    stage: DossierWorkflowStage,
    t: (key: string) => string,
): string {
    return t(STAGE_HINTS[stage]);
}

export type DossierStageCompletionState = 'completed' | 'incomplete';

/**
 * Derive done/not-done states for overview workflow links.
 */
export function dossierStageCompletion(
    dossier: Dossier,
): Record<DossierWorkflowStage, DossierStageCompletionState> {
    const prepareDone = dossier.document_requests.length > 0;
    // Invite is complete when portal access exists or the dossier already moved on.
    const inviteDone =
        dossier.access_grants.some((grant) => grant.is_valid) ||
        dossier.status === 'in_review' ||
        dossier.status === 'completed';
    const reviewDone = dossier.status === 'completed';

    return {
        prepare: prepareDone ? 'completed' : 'incomplete',
        invite: inviteDone ? 'completed' : 'incomplete',
        review: reviewDone ? 'completed' : 'incomplete',
    };
}
