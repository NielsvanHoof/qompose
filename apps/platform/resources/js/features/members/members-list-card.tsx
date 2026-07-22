import { Form } from '@inertiajs/react';
import WorkspaceMemberController from '@/actions/App/Http/Controllers/Tenancy/WorkspaceMemberController';
import EmptyState from '@/components/empty-state';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { RoleOption, WorkspaceMember } from '@/features/members/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';

/**
 * Active and suspended workspace members with role and membership actions.
 */
export default function MembersListCard({
    members,
    roleOptions,
}: {
    members: WorkspaceMember[];
    roleOptions: RoleOption[];
}) {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('Members')}</CardTitle>
                <CardDescription>
                    {members.length === 1
                        ? t('1 member')
                        : t(':count members', { count: members.length })}
                </CardDescription>
            </CardHeader>
            <CardContent>
                {members.length === 0 ? (
                    <EmptyState title={t('No members yet.')} />
                ) : (
                    <div className="divide-y rounded-md border">
                        {members.map((member) => {
                            const roleFormId = `member-role-${member.id}`;
                            const roleMissingFromOptions =
                                Boolean(member.role) &&
                                !roleOptions.some(
                                    (option) => option.value === member.role,
                                );

                            return (
                                <div
                                    key={member.id}
                                    className="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div className="min-w-0">
                                        <p className="font-medium">
                                            {member.name}
                                            {member.is_current_user
                                                ? ` (${t('you')})`
                                                : ''}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {member.email}
                                            {member.status === 'suspended'
                                                ? ` · ${t('Suspended')}`
                                                : ''}
                                        </p>
                                    </div>

                                    <div className="flex flex-wrap items-center gap-2">
                                        <Form
                                            id={roleFormId}
                                            {...WorkspaceMemberController.update.form(
                                                {
                                                    tenant: currentWorkspace,
                                                    membership: member.id,
                                                },
                                            )}
                                            options={{ preserveScroll: true }}
                                            className="flex items-center gap-2"
                                        >
                                            {({ processing }) => (
                                                <Select
                                                    name="role"
                                                    defaultValue={
                                                        member.role ?? undefined
                                                    }
                                                    disabled={
                                                        processing ||
                                                        member.status ===
                                                            'suspended'
                                                    }
                                                    // Submit as soon as a new role is chosen.
                                                    onValueChange={() => {
                                                        queueMicrotask(() => {
                                                            const form =
                                                                document.getElementById(
                                                                    roleFormId,
                                                                );

                                                            if (
                                                                form instanceof
                                                                HTMLFormElement
                                                            ) {
                                                                form.requestSubmit();
                                                            }
                                                        });
                                                    }}
                                                >
                                                    <SelectTrigger
                                                        aria-label={t('Role')}
                                                        className="min-w-40"
                                                    >
                                                        <SelectValue
                                                            placeholder={t(
                                                                'Role',
                                                            )}
                                                        />
                                                    </SelectTrigger>
                                                    <SelectContent className="bg-background">
                                                        {roleOptions.map(
                                                            (option) => (
                                                                <SelectItem
                                                                    key={
                                                                        option.value
                                                                    }
                                                                    value={
                                                                        option.value
                                                                    }
                                                                >
                                                                    {
                                                                        option.label
                                                                    }
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                        {roleMissingFromOptions &&
                                                            member.role && (
                                                                <SelectItem
                                                                    value={
                                                                        member.role
                                                                    }
                                                                >
                                                                    {member.role_label ??
                                                                        member.role}
                                                                </SelectItem>
                                                            )}
                                                    </SelectContent>
                                                </Select>
                                            )}
                                        </Form>

                                        {member.status === 'active' &&
                                            !member.is_current_user && (
                                                <Form
                                                    {...WorkspaceMemberController.update.form(
                                                        {
                                                            tenant: currentWorkspace,
                                                            membership:
                                                                member.id,
                                                        },
                                                    )}
                                                    options={{
                                                        preserveScroll: true,
                                                    }}
                                                >
                                                    {({ processing }) => (
                                                        <>
                                                            <input
                                                                type="hidden"
                                                                name="status"
                                                                value="suspended"
                                                            />
                                                            <Button
                                                                type="submit"
                                                                variant="outline"
                                                                size="sm"
                                                                disabled={
                                                                    processing
                                                                }
                                                            >
                                                                {t('Suspend')}
                                                            </Button>
                                                        </>
                                                    )}
                                                </Form>
                                            )}

                                        <Form
                                            {...WorkspaceMemberController.destroy.form(
                                                {
                                                    tenant: currentWorkspace,
                                                    membership: member.id,
                                                },
                                            )}
                                            options={{ preserveScroll: true }}
                                        >
                                            {({ processing }) => (
                                                <Button
                                                    type="submit"
                                                    variant="ghost"
                                                    size="sm"
                                                    disabled={processing}
                                                >
                                                    {t('Remove')}
                                                </Button>
                                            )}
                                        </Form>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
