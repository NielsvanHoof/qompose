/** One row in the staff workspace notification inbox. */
export type WorkspaceNotification = {
    id: string;
    type: string;
    message: string;
    dossier_id: number;
    dossier_title: string;
    client_name: string;
    dossier_url: string;
    read_at: string | null;
    created_at: string;
};

/** Shared Inertia prop for the top-bar bell. */
export type WorkspaceNotificationsSummary = {
    unread_count: number;
    items: WorkspaceNotification[];
};
