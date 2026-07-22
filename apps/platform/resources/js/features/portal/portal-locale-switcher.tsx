import { router } from '@inertiajs/react';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

const setLocaleCookie = (locale: string): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = 365 * 24 * 60 * 60;
    // Cookie must match Laravel SetLocale middleware; Cookie Store API is not universal.
    // biome-ignore lint/suspicious/noDocumentCookie: locale cookie syncs with Laravel middleware
    document.cookie = `locale=${locale};path=/;max-age=${maxAge};SameSite=Lax`;
};

/**
 * Guest portal language control. Sets the locale cookie and reloads so
 * shared Inertia translations refresh. No auth endpoint — portal guests
 * are not signed in.
 */
export default function PortalLocaleSwitcher({
    className,
    variant = 'default',
}: {
    className?: string;
    variant?: 'default' | 'onPrimary';
}) {
    const { t, locale, availableLocales } = useTranslation();

    const selectLocale = (nextLocale: string): void => {
        if (nextLocale === locale) {
            return;
        }

        setLocaleCookie(nextLocale);
        router.reload({
            preserveUrl: true,
        });
    };

    const isOnPrimary = variant === 'onPrimary';

    return (
        <fieldset
            className={cn(
                'm-0 inline-flex items-center gap-1 rounded-md border-0 p-1',
                isOnPrimary ? 'bg-primary-foreground/15' : 'bg-muted',
                className,
            )}
            aria-label={t('Language')}
        >
            {availableLocales.map(({ code, label }) => {
                const isActive = locale === code;

                return (
                    <button
                        type="button"
                        key={code}
                        onClick={() => selectLocale(code)}
                        className={cn(
                            'rounded px-2.5 py-1 text-xs font-medium transition-colors',
                            isOnPrimary
                                ? isActive
                                    ? 'bg-primary-foreground/25 text-primary-foreground'
                                    : 'text-primary-foreground/75 hover:text-primary-foreground'
                                : isActive
                                  ? 'bg-background text-foreground shadow-xs'
                                  : 'text-muted-foreground hover:text-foreground',
                        )}
                    >
                        {t(label)}
                    </button>
                );
            })}
        </fieldset>
    );
}
