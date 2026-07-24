import { Form } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { useEffect, useRef, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { useTranslation } from '@/hooks/use-translation';
import { OTP_MAX_LENGTH, OTP_SLOTS } from '@/hooks/use-two-factor-auth';
import { confirm } from '@/routes/two-factor';

/**
 * Confirm 2FA with the authenticator app OTP after scanning the QR code.
 */
export default function TwoFactorVerificationStep({
    onClose,
    onBack,
}: {
    onClose: () => void;
    onBack: () => void;
}) {
    const { t } = useTranslation();
    const [code, setCode] = useState<string>('');
    const pinInputContainerRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        setTimeout(() => {
            pinInputContainerRef.current?.querySelector('input')?.focus();
        }, 0);
    }, []);

    return (
        <Form
            {...confirm.form()}
            onSuccess={() => onClose()}
            resetOnError
            resetOnSuccess
        >
            {({
                processing,
                errors,
            }: {
                processing: boolean;
                errors?: { confirmTwoFactorAuthentication?: { code?: string } };
            }) => (
                <>
                    <div
                        ref={pinInputContainerRef}
                        className="relative w-full space-y-3"
                    >
                        <div className="flex w-full flex-col items-center space-y-3 py-2">
                            <InputOTP
                                id="otp"
                                name="code"
                                maxLength={OTP_MAX_LENGTH}
                                onChange={setCode}
                                disabled={processing}
                                pattern={REGEXP_ONLY_DIGITS}
                                autoFocus
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
                            <InputError
                                message={
                                    errors?.confirmTwoFactorAuthentication?.code
                                }
                            />
                        </div>

                        <div className="flex w-full space-x-5">
                            <Button
                                type="button"
                                variant="outline"
                                className="flex-1"
                                onClick={onBack}
                                disabled={processing}
                            >
                                {t('Back')}
                            </Button>
                            <Button
                                type="submit"
                                className="flex-1"
                                disabled={
                                    processing || code.length < OTP_MAX_LENGTH
                                }
                            >
                                {t('Confirm')}
                            </Button>
                        </div>
                    </div>
                </>
            )}
        </Form>
    );
}
