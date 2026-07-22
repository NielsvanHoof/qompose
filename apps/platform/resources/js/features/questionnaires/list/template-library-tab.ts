/**
 * System vs custom buckets on the template library index.
 * Synced to ?tab= so pagination (withQueryString) keeps the active panel.
 */
export const TEMPLATE_LIBRARY_TABS = ['system', 'custom'] as const;

export type TemplateLibraryTab = (typeof TEMPLATE_LIBRARY_TABS)[number];

export const DEFAULT_TEMPLATE_LIBRARY_TAB: TemplateLibraryTab = 'system';

export function isTemplateLibraryTab(value: string): value is TemplateLibraryTab {
    return TEMPLATE_LIBRARY_TABS.includes(value as TemplateLibraryTab);
}

export function readTemplateLibraryTabFromUrl(): TemplateLibraryTab | null {
    if (typeof window === 'undefined') {
        return null;
    }

    const tab = new URLSearchParams(window.location.search).get('tab');

    return tab && isTemplateLibraryTab(tab) ? tab : null;
}

export function writeTemplateLibraryTabToUrl(tab: TemplateLibraryTab): void {
    const url = new URL(window.location.href);
    url.searchParams.set('tab', tab);
    window.history.replaceState({}, '', url);
}
