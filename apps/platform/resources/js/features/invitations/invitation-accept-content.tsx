import { Form } from '@inertiajs/react';
import AcceptTenantInvitationController from '@/actions/App/Http/Controllers/Tenancy/AcceptTenantInvitationController';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import StatusMessage from '@/components/status-message';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { InvitationShowProps } from '@/features/invitations/types';
import { useTranslation } from '@/hooks/use-translation';
import { login } from '@/routes';

/**
 * Accept / register UI for a workspace invitation token.
 * Keeps the Inertia page thin — layout chrome stays on the page.
 */
export default function InvitationAcceptContent({
    token,
    firm,
    invitation,
    auth,
    can_register: canRegister,
    passwordRules,
}: InvitationShowProps) {
    const { t } = useTranslation();

    return (
        <div className="flex flex-col gap-6">
            <StatusMessage>
                {t(':firm invited :email to join as :role.', {
                    firm: firm.name,
                    email: invitation.email,
                    role: invitation.role_label,
                })}
            </StatusMessage>

            {auth.authenticated && auth.email_matches && (
                <Form
                    {...AcceptTenantInvitationController.accept.form(token)}
                    className="flex flex-col gap-4"
                >
                    {({ processing }) => (
                        <Button
                            type="submit"
                            className="w-full"
                            disabled={processing}
                        >
                            {processing && <Spinner />}
                            {t('Accept invitation')}
                        </Button>
                    )}
                </Form>
            )}

            {auth.authenticated && !auth.email_matches && (
                <div className="space-y-3 text-sm text-muted-foreground">
                    <p>
                        {t(
                            'You are signed in as :email. Sign in with :invite to accept.',
                            {
                                email: auth.user_email ?? '',
                                invite: invitation.email,
                            },
                        )}
                    </p>
                    <TextLink href={login()}>{t('Log in')}</TextLink>
                </div>
            )}

            {!auth.authenticated && (
                <div className="space-y-6">
                    <p className="text-sm text-muted-foreground">
                        {t('Already have an account?')}{' '}
                        <TextLink href={login()}>{t('Log in')}</TextLink>{' '}
                        {t('with :email, then return to this page.', {
                            email: invitation.email,
                        })}
                    </p>

                    {canRegister && (
                        <Form
                            {...AcceptTenantInvitationController.register.form(
                                token,
                            )}
                            resetOnSuccess={[
                                'password',
                                'password_confirmation',
                            ]}
                            disableWhileProcessing
                            className="flex flex-col gap-6"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <input
                                        type="hidden"
                                        name="email"
                                        value={invitation.email}
                                    />

                                    <div className="grid gap-6">
                                        <div className="grid gap-2">
                                            <Label htmlFor="name">
                                                {t('Name')}
                                            </Label>
                                            <Input
                                                id="name"
                                                type="text"
                                                required
                                                autoFocus
                                                autoComplete="name"
                                                name="name"
                                                placeholder={t('Full name')}
                                            />
                                            <InputError message={errors.name} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="email">
                                                {t('Email address')}
                                            </Label>
                                            <Input
                                                id="email"
                                                type="email"
                                                value={invitation.email}
                                                disabled
                                                readOnly
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="password">
                                                {t('Password')}
                                            </Label>
                                            <PasswordInput
                                                id="password"
                                                required
                                                autoComplete="new-password"
                                                name="password"
                                                placeholder={t('Password')}
                                                passwordrules={passwordRules}
                                            />
                                            <InputError
                                                message={errors.password}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="password_confirmation">
                                                {t('Confirm password')}
                                            </Label>
                                            <PasswordInput
                                                id="password_confirmation"
                                                required
                                                autoComplete="new-password"
                                                name="password_confirmation"
                                                placeholder={t(
                                                    'Confirm password',
                                                )}
                                                passwordrules={passwordRules}
                                            />
                                            <InputError
                                                message={
                                                    errors.password_confirmation
                                                }
                                            />
                                        </div>

                                        <Button
                                            type="submit"
                                            className="w-full"
                                            disabled={processing}
                                        >
                                            {processing && <Spinner />}
                                            {t('Create account and join')}
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    )}
                </div>
            )}
        </div>
    );
}
