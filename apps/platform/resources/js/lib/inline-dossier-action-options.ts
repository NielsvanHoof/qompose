const inlineDossierReloadProps: string[] = ['dossier', 'flash'];

/**
 * Partial reload after inline dossier mutations — keeps scroll, refreshes dossier + flash toast.
 */
export const inlineDossierActionOptions = {
    preserveScroll: true,
    only: inlineDossierReloadProps,
};
