import { Head, setLayoutProps } from '@inertiajs/react';
import Heading from '@/components/heading';
import CreateFirmForm from '@/features/workspaces/create-firm-form';
import { useTranslation } from '@/hooks/use-translation';
import { create as createFirm, store as storeFirm } from '@/routes/firms';

/**
 * Create an additional firm for the current user.
 * First-run firm creation lives on the onboarding page.
 */
export default function CreateFirm() {
    const { t } = useTranslation();

    setLayoutProps({
        breadcrumbs: [
            {
                title: t('New firm'),
                href: createFirm(),
            },
        ],
    });

    return (
        <>
            <Head title={t('New firm')} />

            <div className="mx-auto w-full max-w-xl p-4 md:p-8">
                <Heading
                    title={t('New firm')}
                    description={t(
                        'Add another accounting firm. You can switch between your firms at any time.',
                    )}
                />

                <CreateFirmForm
                    action={storeFirm.form()}
                    submitLabel={t('Create firm')}
                />
            </div>
        </>
    );
}
