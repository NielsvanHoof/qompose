import { Head } from '@inertiajs/react';
import FirmOnboardingCard from '@/features/workspaces/firm-onboarding-card';
import { useTranslation } from '@/hooks/use-translation';

/**
 * First-run firm setup page.
 */
export default function FirmOnboarding() {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Set up your firm')} />

            <div className="mx-auto flex min-h-screen w-full max-w-xl items-center p-4 md:p-8">
                <FirmOnboardingCard />
            </div>
        </>
    );
}
