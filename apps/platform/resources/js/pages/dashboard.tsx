import { Head, setLayoutProps } from '@inertiajs/react';
import Heading from '@/components/heading';
import FirmPickerGrid from '@/features/workspaces/firm-picker-grid';
import type { Firm } from '@/features/workspaces/types';
import { useTranslation } from '@/hooks/use-translation';
import { dashboard } from '@/routes';

type PageProps = {
    firms: Firm[];
};

/**
 * Global dashboard — pick a firm to enter its workspace.
 */
export default function Dashboard({ firms }: PageProps) {
    const { t } = useTranslation();
    setLayoutProps({
        breadcrumbs: [
            {
                title: t('Dashboard'),
                href: dashboard(),
            },
        ],
    });

    return (
        <>
            <Head title={t('Dashboard')} />

            <div className="mx-auto flex w-full max-w-5xl flex-col gap-8 p-4 md:p-8">
                <Heading
                    level={1}
                    title={t('Choose a firm')}
                    description={t(
                        'Choose the firm whose client dossiers you want to manage.',
                    )}
                />

                <FirmPickerGrid firms={firms} />
            </div>
        </>
    );
}
