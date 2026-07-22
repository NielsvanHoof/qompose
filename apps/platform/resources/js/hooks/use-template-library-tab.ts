import { useCallback, useEffect, useState } from 'react';
import {
    DEFAULT_TEMPLATE_LIBRARY_TAB,
    type TemplateLibraryTab,
    readTemplateLibraryTabFromUrl,
    writeTemplateLibraryTabToUrl,
} from '@/features/questionnaires/list/template-library-tab';

/**
 * Keeps the active template library tab in sync with ?tab=system|custom.
 */
export function useTemplateLibraryTab() {
    const [tab, setTabState] = useState<TemplateLibraryTab>(() => {
        return readTemplateLibraryTabFromUrl() ?? DEFAULT_TEMPLATE_LIBRARY_TAB;
    });

    // Seed the URL on first mount when no ?tab= is present yet.
    useEffect(() => {
        if (!readTemplateLibraryTabFromUrl()) {
            writeTemplateLibraryTabToUrl(tab);
        }
    }, [tab]);

    const setTab = useCallback((next: TemplateLibraryTab) => {
        setTabState(next);
        writeTemplateLibraryTabToUrl(next);
    }, []);

    return [tab, setTab] as const;
}
