import { Head, setLayoutProps } from '@inertiajs/react';
import Heading from '@/components/heading';
import AppearanceTabs from '@/features/settings/appearance-tabs';
import { useTranslation } from '@/hooks/use-translation';
import { edit as editAppearance } from '@/routes/appearance';

export default function Appearance() {
    const { t } = useTranslation();

    setLayoutProps({
        breadcrumbs: [
            {
                title: t('Appearance settings'),
                href: editAppearance(),
            },
        ],
    });

    return (
        <>
            <Head title={t('Appearance settings')} />

            <h1 className="sr-only">{t('Appearance settings')}</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={t('Appearance settings')}
                    description={t(
                        'Update the appearance settings for your account',
                    )}
                />
                <AppearanceTabs />
            </div>
        </>
    );
}
