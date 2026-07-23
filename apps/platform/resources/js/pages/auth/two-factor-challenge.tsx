import { Form, Head, setLayoutProps } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { useMemo, useState } from 'react';
import InputError, { fieldErrorAriaProps } from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/hooks/use-translation';
import { OTP_MAX_LENGTH, OTP_SLOTS } from '@/hooks/use-two-factor-auth';
import { store } from '@/routes/two-factor/login';

export default function TwoFactorChallenge() {
    const { t } = useTranslation();
    const [showRecoveryInput, setShowRecoveryInput] = useState<boolean>(false);
    const [code, setCode] = useState<string>('');

    const authConfigContent = useMemo<{
        title: string;
        description: string;
        toggleText: string;
    }>(() => {
        if (showRecoveryInput) {
            return {
                title: t('Recovery code'),
                description: t(
                    'Please confirm access to your account by entering one of your emergency recovery codes.',
                ),
                toggleText: t('login using an authentication code'),
            };
        }

        return {
            title: t('Authentication code'),
            description: t(
                'Enter the authentication code provided by your authenticator application.',
            ),
            toggleText: t('login using a recovery code'),
        };
    }, [showRecoveryInput, t]);

    setLayoutProps({
        title: authConfigContent.title,
        description: authConfigContent.description,
    });

    const toggleRecoveryMode = (clearErrors: () => void): void => {
        setShowRecoveryInput(!showRecoveryInput);
        clearErrors();
        setCode('');
    };

    return (
        <>
            <Head title={t('Two-factor authentication')} />

            <div className="space-y-6">
                <Form
                    {...store.form()}
                    className="space-y-4"
                    resetOnError
                    resetOnSuccess={!showRecoveryInput}
                >
                    {({ errors, processing, clearErrors }) => (
                        <>
                            {showRecoveryInput ? (
                                <div className="grid gap-2">
                                    <Label htmlFor="recovery_code">
                                        {t('Recovery code')}
                                    </Label>
                                    <Input
                                        id="recovery_code"
                                        name="recovery_code"
                                        type="text"
                                        placeholder={t('Enter recovery code')}
                                        autoFocus={showRecoveryInput}
                                        autoComplete="one-time-code"
                                        required
                                        {...fieldErrorAriaProps(
                                            'recovery_code-error',
                                            errors.recovery_code,
                                        )}
                                    />
                                    <InputError
                                        id="recovery_code-error"
                                        message={errors.recovery_code}
                                    />
                                </div>
                            ) : (
                                <div className="flex flex-col items-center justify-center space-y-3 text-center">
                                    <Label
                                        htmlFor="two-factor-code"
                                        className="sr-only"
                                    >
                                        {t('Authentication code')}
                                    </Label>
                                    <div className="flex w-full items-center justify-center">
                                        <InputOTP
                                            id="two-factor-code"
                                            name="code"
                                            maxLength={OTP_MAX_LENGTH}
                                            value={code}
                                            onChange={(value) => setCode(value)}
                                            disabled={processing}
                                            pattern={REGEXP_ONLY_DIGITS}
                                            autoFocus
                                            aria-label={t(
                                                'Authentication code',
                                            )}
                                            {...fieldErrorAriaProps(
                                                'code-error',
                                                errors.code,
                                            )}
                                        >
                                            <InputOTPGroup>
                                                {OTP_SLOTS.map((slot) => (
                                                    <InputOTPSlot
                                                        key={slot.id}
                                                        index={slot.index}
                                                    />
                                                ))}
                                            </InputOTPGroup>
                                        </InputOTP>
                                    </div>
                                    <InputError
                                        id="code-error"
                                        message={errors.code}
                                    />
                                </div>
                            )}

                            <Button
                                type="submit"
                                className="w-full"
                                disabled={processing}
                            >
                                {t('Continue')}
                            </Button>

                            <div className="text-center text-sm text-muted-foreground">
                                <span>{t('or you can')} </span>
                                <button
                                    type="button"
                                    className="cursor-pointer text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                    onClick={() =>
                                        toggleRecoveryMode(clearErrors)
                                    }
                                >
                                    {authConfigContent.toggleText}
                                </button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
