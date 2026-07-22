import { Form } from '@inertiajs/react';
import WorkspaceMemberController from '@/actions/App/Http/Controllers/Tenancy/WorkspaceMemberController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import type { RoleOption } from '@/features/members/types';
import { useCurrentWorkspace } from '@/hooks/use-current-workspace';
import { useTranslation } from '@/hooks/use-translation';

/**
 * Invite a colleague by email and assign an initial workspace role.
 */
export default function InviteMemberForm({
    roleOptions,
}: {
    roleOptions: RoleOption[];
}) {
    const currentWorkspace = useCurrentWorkspace();
    const { t } = useTranslation();
    // Prefer adviser as the default invite role when it is assignable.
    const defaultRole =
        roleOptions.find((option) => option.value === 'adviser')?.value ??
        roleOptions[0]?.value ??
        '';

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('Invite member')}</CardTitle>
                <CardDescription>
                    {t(
                        'Send an email invitation to join this workspace with a role.',
                    )}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Form
                    {...WorkspaceMemberController.store.form(currentWorkspace)}
                    className="grid gap-4 sm:grid-cols-[1fr_12rem_auto] sm:items-end"
                    resetOnSuccess
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="email">
                                    {t('Email address')}
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoComplete="email"
                                    placeholder={t('email@example.com')}
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="role">{t('Role')}</Label>
                                <Select
                                    required
                                    name="role"
                                    defaultValue={defaultRole}
                                >
                                    <SelectTrigger id="role" className="w-full">
                                        <SelectValue placeholder={t('Role')} />
                                    </SelectTrigger>
                                    <SelectContent className="bg-background">
                                        {roleOptions.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.role} />
                            </div>

                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                {t('Send invite')}
                            </Button>
                        </>
                    )}
                </Form>
            </CardContent>
        </Card>
    );
}
