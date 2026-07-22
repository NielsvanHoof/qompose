/** Client row for the clients index page. */
export type ClientSummary = {
    id: number;
    name: string;
    email: string;
    dossiers_count: number;
};

export type ClientDetails = {
    id: number;
    name: string;
    email: string;
    active_dossiers_count: number;
    archived_dossiers_count: number;
};

export type ArchivedClientSummary = ClientSummary & {
    archived_at: string;
};
