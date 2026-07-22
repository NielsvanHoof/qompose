export type WorkspaceMember = {
    id: number;
    user_id: number;
    name: string;
    email: string;
    status: 'active' | 'suspended' | 'invited';
    role: string | null;
    role_label: string | null;
    joined_at: string | null;
    last_accessed_at: string | null;
    is_current_user: boolean;
};

export type WorkspaceInvitation = {
    id: number;
    email: string;
    role: string;
    role_label: string;
    invited_at: string;
    expires_at: string;
};

export type RoleOption = {
    value: string;
    label: string;
};
