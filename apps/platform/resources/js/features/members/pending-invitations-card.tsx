import { Form } from '@inertiajs/react';
import TenantInvitationController from '@/actions/App/Http/Controllers/Tenancy/TenantInvitationController';
import ConfirmDestroyDialog from '@/components/confirm-destroy-dialog';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { WorkspaceInvitation } from '@/features/members/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';

/**
 * Pending workspace invitations with resend and revoke actions.
 */
export default function PendingInvitationsCard({
    invitations,
}: {
    invitations: WorkspaceInvitation[];
}) {
    const currentWorkspace = useCurrentWorkspace();
    const { t, locale } = useTranslation();

    if (invitations.length === 0) {
        return null;
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('Pending invitations')}</CardTitle>
                <CardDescription>
                    {invitations.length === 1
                        ? t('1 invitation waiting to be accepted')
                        : t(':count invitations waiting to be accepted', {
                              count: invitations.length,
                          })}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div className="divide-y rounded-md border">
                    {invitations.map((invitation) => (
                        <div
                            key={invitation.id}
                            className="flex flex-wrap items-center justify-between gap-3 px-4 py-3"
                        >
                            <div>
                                <p className="font-medium">
                                    {invitation.email}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {invitation.role_label} ·{' '}
                                    {t('Expires :date', {
                                        date: new Date(
                                            invitation.expires_at,
                                        ).toLocaleString(locale),
                                    })}
                                </p>
                            </div>

                            <div className="flex flex-wrap gap-2">
                                <Form
                                    {...TenantInvitationController.resend.form({
                                        tenant: currentWorkspace,
                                        invitation: invitation.id,
                                    })}
                                >
                                    {({ processing }) => (
                                        <Button
                                            type="submit"
                                            variant="outline"
                                            size="sm"
                                            disabled={processing}
                                        >
                                            {t('Resend')}
                                        </Button>
                                    )}
                                </Form>

                                <ConfirmDestroyDialog
                                    title={t('Revoke invitation?')}
                                    description={t(
                                        'The invite link for :email will stop working. You can send a new invitation later.',
                                        { email: invitation.email },
                                    )}
                                    confirmLabel={t('Revoke')}
                                    form={TenantInvitationController.destroy.form(
                                        {
                                            tenant: currentWorkspace,
                                            invitation: invitation.id,
                                        },
                                    )}
                                    options={{ preserveScroll: true }}
                                    trigger={
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                        >
                                            {t('Revoke')}
                                        </Button>
                                    }
                                />
                            </div>
                        </div>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}
