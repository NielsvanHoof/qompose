import { createContext, type ReactNode, useContext } from 'react';

/**
 * Staff dossier capability flags from the show page.
 * Avoids threading can_manage / can_review / can_download through every card.
 */
export type DossierPermissions = {
    canManage: boolean;
    canReview: boolean;
    canDownload: boolean;
};

const DossierPermissionsContext = createContext<DossierPermissions | null>(
    null,
);

export function DossierPermissionsProvider({
    value,
    children,
}: {
    value: DossierPermissions;
    children: ReactNode;
}) {
    return (
        <DossierPermissionsContext.Provider value={value}>
            {children}
        </DossierPermissionsContext.Provider>
    );
}

export function useDossierPermissions(): DossierPermissions {
    const value = useContext(DossierPermissionsContext);

    if (value === null) {
        throw new Error(
            'useDossierPermissions must be used within DossierPermissionsProvider.',
        );
    }

    return value;
}
