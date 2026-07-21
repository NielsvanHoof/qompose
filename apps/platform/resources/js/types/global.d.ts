import type { WorkspaceNotificationsSummary } from '@/features/notifications/types';
import type { Firm } from '@/features/workspaces/types';
import type { Auth } from '@/types/auth';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            workspaces: Firm[];
            current_firm: Firm | null;
            notifications: WorkspaceNotificationsSummary | null;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
