import { Head, setLayoutProps } from '@inertiajs/react';
import Heading from '@/components/heading';
import LocaleTabs from '@/features/settings/locale-tabs';
import { useTranslation } from '@/hooks/use-translation';
import { edit as editLanguage } from '@/routes/language';

export default function Language() {
    const { t } = useTranslation();

    setLayoutProps({
        breadcrumbs: [
            {
                title: t('Language settings'),
                href: editLanguage(),
            },
        ],
    });

    return (
        <>
            <Head title={t('Language settings')} />

            <h1 className="sr-only">{t('Language settings')}</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={t('Language settings')}
                    description={t(
                        'Update the language used across the application',
                    )}
                />
                <LocaleTabs />
            </div>
        </>
    );
}
