import { router } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import { destroy } from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyRegistrationController';
import EmptyState from '@/components/empty-state';
import Heading from '@/components/heading';
import PasskeyItem from '@/features/security/passkeys/passkey-item';
import PasskeyRegistration from '@/features/security/passkeys/passkey-register';
import { useTranslation } from '@/hooks/use-translation';
import type { Passkey } from '@/types/auth';

export type Props = {
    canManagePasskeys?: boolean;
    passkeys?: Passkey[];
};

export default function ManagePasskeys(props: Props) {
    const passkeys = props.passkeys ?? [];
    const { t } = useTranslation();

    const handleDelete = (id: number, onError: () => void) => {
        router.delete(destroy.url(id), {
            preserveScroll: true,
            only: ['passkeys', 'flash'],
            onError,
        });
    };

    const handleRegisterSuccess = () => {
        router.reload({ only: ['passkeys', 'flash'] });
    };

    if (!(props.canManagePasskeys ?? false)) {
        return null;
    }

    return (
        <div className="space-y-6">
            <Heading
                level={2}
                variant="small"
                title={t('Passkeys')}
                description={t('Manage your passkeys for passwordless sign-in')}
            />

            <div className="overflow-hidden rounded-lg border border-border">
                {passkeys.length > 0 ? (
                    passkeys.map((passkey) => (
                        <PasskeyItem
                            key={passkey.id}
                            passkey={passkey}
                            onDelete={handleDelete}
                        />
                    ))
                ) : (
                    <EmptyState
                        variant="panel"
                        icon={KeyRound}
                        title={t('No passkeys yet')}
                        description={t(
                            'Add a passkey to sign in without a password',
                        )}
                    />
                )}
            </div>

            <PasskeyRegistration onSuccess={handleRegisterSuccess} />
        </div>
    );
}
