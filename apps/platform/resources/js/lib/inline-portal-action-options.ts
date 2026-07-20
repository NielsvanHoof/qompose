const inlinePortalReloadProps: string[] = ['dossier', 'flash'];

/**
 * Partial reload after inline portal mutations — keeps scroll, refreshes dossier + flash toast.
 */
export const inlinePortalActionOptions = {
    preserveScroll: true,
    only: inlinePortalReloadProps,
};
