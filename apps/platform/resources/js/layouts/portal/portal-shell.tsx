import type { ReactNode } from 'react';
import AppLogoIcon from '@/components/app-shell/app-logo-icon';
import BrandShieldWatermark from '@/components/brand-shield-watermark';
import SkipToContent from '@/components/skip-to-content';
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
 * Mirrors the builder’s hierarchy: firm is quiet chrome, dossier title is the signal.
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
        <div className="min-h-svh bg-secondary pl-[env(safe-area-inset-left)] pr-[env(safe-area-inset-right)]">
            <SkipToContent />
            <header className="relative isolate overflow-hidden border-b border-primary/10 bg-primary pt-[env(safe-area-inset-top)] text-primary-foreground">
                <BrandShieldWatermark
                    variant="light"
                    className="-right-24 -bottom-16 h-56 w-80 opacity-25"
                />
                <BrandShieldWatermark
                    variant="primary"
                    className="-bottom-20 -left-16 h-48 w-72 opacity-40"
                />
                <div className="relative z-10 mx-auto flex w-full max-w-3xl flex-col gap-5 px-4 py-7 md:px-8">
                    <div className="flex items-start justify-between gap-3">
                        <div className="flex min-w-0 items-center gap-2.5">
                            <div className="flex size-9 shrink-0 items-center justify-center rounded-md bg-primary-foreground/15">
                                <AppLogoIcon className="size-5 text-primary-foreground" />
                            </div>
                            <div className="min-w-0">
                                <p className="text-[0.7rem] font-medium tracking-wide text-primary-foreground/70 uppercase">
                                    {t('Secure client portal')}
                                </p>
                                <p className="truncate text-sm text-primary-foreground/90">
                                    {firmName}
                                </p>
                            </div>
                        </div>
                        <PortalLocaleSwitcher variant="onPrimary" />
                    </div>
                    <div className="space-y-1.5">
                        <h1 className="text-2xl font-semibold tracking-tight text-balance md:text-[1.75rem]">
                            {title}
                        </h1>
                        {subtitle ? (
                            <p className="text-sm text-primary-foreground/80">
                                {subtitle}
                            </p>
                        ) : null}
                        {meta ? (
                            <p className="text-xs text-primary-foreground/65">
                                {meta}
                            </p>
                        ) : null}
                    </div>
                </div>
            </header>

            <main
                id="main-content"
                tabIndex={-1}
                className="mx-auto flex w-full max-w-3xl flex-col gap-5 px-4 py-6 pb-[max(1.5rem,env(safe-area-inset-bottom))] md:gap-6 md:px-8 md:py-8"
            >
                {children}
            </main>
        </div>
    );
}
