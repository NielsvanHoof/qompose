import { Head, setLayoutProps } from '@inertiajs/react';
import InvitationAcceptContent from '@/features/invitations/invitation-accept-content';
import type { InvitationShowProps } from '@/features/invitations/types';
import { useTranslation } from '@/hooks/use-translation';

/**
 * Accept a workspace invitation — login/accept for existing users, or register.
 * Thin page: layout chrome only; UI lives in features/invitations.
 */
export default function InvitationShow(props: InvitationShowProps) {
    const { t } = useTranslation();

    setLayoutProps({
        title: t('Join :firm', { firm: props.firm.name }),
        description: t('You have been invited as :role.', {
            role: props.invitation.role_label,
        }),
    });

    return (
        <>
            <Head title={t('Workspace invitation')} />
            <InvitationAcceptContent {...props} />
        </>
    );
}
