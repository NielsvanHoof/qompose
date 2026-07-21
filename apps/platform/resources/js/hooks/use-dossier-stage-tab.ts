import { useCallback, useEffect, useState } from 'react';
import {
    type DossierStageTab,
    defaultTabForStatus,
    readStageFromUrl,
    writeStageToUrl,
} from '@/features/dossiers/stage/dossier-stage';
import type { DossierStatus } from '@/features/dossiers/types';

/**
 * Keeps the active dossier stage tab in sync with ?stage= query param.
 */
export function useDossierStageTab(status: DossierStatus) {
    const [tab, setTabState] = useState<DossierStageTab>(() => {
        return readStageFromUrl() ?? defaultTabForStatus(status);
    });

    useEffect(() => {
        if (!readStageFromUrl()) {
            writeStageToUrl(tab);
        }
    }, [tab]);

    const setTab = useCallback((stage: DossierStageTab) => {
        setTabState(stage);
        writeStageToUrl(stage);
    }, []);

    return [tab, setTab] as const;
}
