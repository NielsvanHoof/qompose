import { Head, setLayoutProps } from '@inertiajs/react';
import Heading from '@/components/heading';
import InviteMemberForm from '@/features/members/invite-member-form';
import MembersListCard from '@/features/members/members-list-card';
import PendingInvitationsCard from '@/features/members/pending-invitations-card';
import type {
    RoleOption,
    WorkspaceInvitation,
    WorkspaceMember,
} from '@/features/members/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';
import { index as membersIndex } from '@/routes/workspaces/members';

/**
 * Workspace staff members — invite, change roles, suspend, or remove.
 */
export default function WorkspaceMembersIndex({
    members,
    invitations,
    role_options: roleOptions,
}: {
    members: WorkspaceMember[];
    invitations: WorkspaceInvitation[];
    role_options: RoleOption[];
    can_assign_owner?: boolean;
}) {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();

    setLayoutProps({
        breadcrumbs: [
            {
                title: t('Members'),
                href: membersIndex(currentWorkspace),
            },
        ],
    });

    return (
        <>
            <Head title={t('Members')} />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-8">
                <Heading
                    level={1}
                    title={t('Members')}
                    description={t(
                        'Invite colleagues and manage who can access this workspace.',
                    )}
                />

                <InviteMemberForm roleOptions={roleOptions} />
                <PendingInvitationsCard invitations={invitations} />
                <MembersListCard members={members} roleOptions={roleOptions} />
            </div>
        </>
    );
}
