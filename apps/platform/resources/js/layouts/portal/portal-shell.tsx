import type { ReactNode } from 'react';
import AppLogoIcon from '@/components/app-shell/app-logo-icon';
import BrandShieldWatermark from '@/components/brand-shield-watermark';
import PortalLocaleSwitcher from '@/features/portal/portal-locale-switcher';
import { useTranslation } from '@/hooks/use-translation';

type PortalShellProps = {
    firmName: string;
    title: string;
    subtitle?: string;
    meta?: string;
    children: ReactNode;
};

/**
 * Branded shell for guest client-portal pages (no staff chrome).
 */
export default function PortalShell({
    firmName,
    title,
    subtitle,
    meta,
    children,
}: PortalShellProps) {
    const { t } = useTranslation();

    return (
        <div className="min-h-svh bg-secondary">
            <header className="relative isolate overflow-hidden border-b border-primary/10 bg-primary text-primary-foreground">
                <BrandShieldWatermark
                    variant="light"
                    className="-right-24 -bottom-16 h-56 w-80 opacity-25"
                />
                <BrandShieldWatermark
                    variant="primary"
                    className="-bottom-20 -left-16 h-48 w-72 opacity-40"
                />
                <div className="relative z-10 mx-auto flex w-full max-w-3xl flex-col gap-4 px-4 py-8 md:px-8">
                    <div className="flex items-start justify-between gap-3">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-md bg-primary-foreground/15">
                                <AppLogoIcon className="size-6 text-primary-foreground" />
                            </div>
                            <div>
                                <p className="text-xs font-medium tracking-wide text-primary-foreground/80 uppercase">
                                    {t('Secure client portal')}
                                </p>
                                <p className="text-sm font-semibold">
                                    {firmName}
                                </p>
                            </div>
                        </div>
                        {/* Compact control so clients can override browser language. */}
                        <PortalLocaleSwitcher variant="onPrimary" />
                    </div>
                    <div className="space-y-1">
                        <h1 className="text-2xl font-semibold tracking-tight text-balance">
                            {title}
                        </h1>
                        {subtitle && (
                            <p className="text-sm text-primary-foreground/85">
                                {subtitle}
                            </p>
                        )}
                        {meta && (
                            <p className="text-xs text-primary-foreground/70">
                                {meta}
                            </p>
                        )}
                    </div>
                </div>
            </header>

            <main className="mx-auto flex w-full max-w-3xl flex-col gap-6 px-4 py-8 md:px-8">
                {children}
            </main>
        </div>
    );
}
