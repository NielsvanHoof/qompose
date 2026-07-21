import type { Dossier, DossierStatus } from '@/features/dossiers/types';

export const STAGE_ORDER = ['prepare', 'invite', 'review'] as const;

export type DossierStageTab = (typeof STAGE_ORDER)[number];

/**
 * English keys for dossier stage labels. Pass through `t()` for display.
 */
export const STAGE_LABELS: Record<DossierStageTab, string> = {
    prepare: 'Prepare',
    invite: 'Invite',
    review: 'Review',
};

/**
 * English keys for dossier stage hints. Pass through `t()` for display.
 */
export const STAGE_HINTS: Record<DossierStageTab, string> = {
    prepare: 'Build the questionnaire, then continue to Invite.',
    invite: 'Send a portal link so the client can upload. Then continue to Review.',
    review: 'Check OCR results, approve or request changes, then complete the dossier.',
};

/**
 * Resolve a translated label for a dossier stage.
 */
export function dossierStageLabel(
    stage: DossierStageTab,
    t: (key: string) => string,
): string {
    return t(STAGE_LABELS[stage]);
}

/**
 * Resolve a translated hint for a dossier stage.
 */
export function dossierStageHint(
    stage: DossierStageTab,
    t: (key: string) => string,
): string {
    return t(STAGE_HINTS[stage]);
}

/**
 * Pick the tab that matches where the dossier is in the workflow.
 */
export function defaultTabForStatus(status: DossierStatus): DossierStageTab {
    switch (status) {
        case 'awaiting_client':
            return 'invite';
        case 'in_review':
        case 'completed':
            return 'review';
        default:
            return 'prepare';
    }
}

export function isDossierStageTab(value: string): value is DossierStageTab {
    return STAGE_ORDER.includes(value as DossierStageTab);
}

export function readStageFromUrl(): DossierStageTab | null {
    if (typeof window === 'undefined') {
        return null;
    }

    const stage = new URLSearchParams(window.location.search).get('stage');

    return stage && isDossierStageTab(stage) ? stage : null;
}

export function writeStageToUrl(stage: DossierStageTab): void {
    const url = new URL(window.location.href);
    url.searchParams.set('stage', stage);
    window.history.replaceState({}, '', url);
}

export type DossierStageProgressState = 'completed' | 'current' | 'upcoming';

/**
 * Derive step states for the workflow progress indicator.
 */
export function dossierStageProgress(
    dossier: Dossier,
    activeTab: DossierStageTab,
): Record<DossierStageTab, DossierStageProgressState> {
    const prepareDone = dossier.document_requests.length > 0;
    const inviteDone =
        dossier.access_grants.some((grant) => grant.is_valid) ||
        dossier.status === 'in_review' ||
        dossier.status === 'completed' ||
        dossier.document_requests.some(
            (request) => request.status !== 'pending',
        );
    const reviewDone = dossier.status === 'completed';

    const completion: Record<DossierStageTab, boolean> = {
        prepare: prepareDone,
        invite: inviteDone,
        review: reviewDone,
    };

    return STAGE_ORDER.reduce(
        (states, stage) => {
            if (stage === activeTab) {
                states[stage] = 'current';
            } else if (completion[stage]) {
                states[stage] = 'completed';
            } else {
                states[stage] = 'upcoming';
            }

            return states;
        },
        {} as Record<DossierStageTab, DossierStageProgressState>,
    );
}
