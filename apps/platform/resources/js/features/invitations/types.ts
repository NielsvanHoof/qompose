/**
 * Props for the workspace invitation accept / register page.
 */
export type InvitationShowProps = {
    token: string;
    firm: { name: string; slug: string };
    invitation: {
        email: string;
        role: string;
        role_label: string;
        expires_at: string;
    };
    auth: {
        authenticated: boolean;
        email_matches: boolean;
        user_email: string | null;
    };
    can_register: boolean;
    passwordRules: string;
};
