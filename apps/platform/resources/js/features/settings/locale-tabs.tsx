import { router } from '@inertiajs/react';
import type { HTMLAttributes } from 'react';
import LocaleController from '@/actions/App/Http/Controllers/Settings/LocaleController';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

const setCookie = (name: string, value: string, days = 365): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;
    // Required for SSR locale hydration; Cookie Store API is not universally supported.
    // biome-ignore lint/suspicious/noDocumentCookie: locale cookie must sync with Laravel middleware
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

export default function LocaleTabs({
    className = '',
    ...props
}: HTMLAttributes<HTMLDivElement>) {
    const { t, locale, availableLocales } = useTranslation();

    const updateLocale = (nextLocale: string): void => {
        if (nextLocale === locale) {
            return;
        }

        localStorage.setItem('locale', nextLocale);
        setCookie('locale', nextLocale);

        router.patch(
            LocaleController.update.url(),
            { locale: nextLocale },
            {
                preserveScroll: true,
            },
        );
    };

    return (
        <div
            className={cn(
                'inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800',
                className,
            )}
            {...props}
        >
            {availableLocales.map(({ code, label }) => (
                <button
                    type="button"
                    key={code}
                    onClick={() => updateLocale(code)}
                    className={cn(
                        'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
                        locale === code
                            ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                            : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
                    )}
                >
                    <span className="text-sm">{t(label)}</span>
                </button>
            ))}
        </div>
    );
}
