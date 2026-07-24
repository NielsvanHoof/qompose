import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import type { ReactNode } from 'react';
import { AppContent } from '@/components/app-shell/app-content';
import { AppShell } from '@/components/app-shell/app-shell';
import SkipToContent from '@/components/skip-to-content';
import DossierStatusBadge from '@/features/dossiers/status/dossier-status-badge';
import type { DossierStatus } from '@/features/dossiers/types';
import { useTranslation } from '@/hooks/use-translation';

type DossierBuilderLayoutProps = {
    children: ReactNode;
    /** Absolute URL back to the dossier overview. */
    backHref?: string;
    title?: string;
    status?: DossierStatus;
};

/**
 * Sidebar-free shell for the dossier form builder.
 * Locks to the viewport so the three-column canvas fills width and height.
 */
export default function DossierBuilderLayout({
    children,
    backHref = '#',
    title = '',
    status,
}: DossierBuilderLayoutProps) {
    const { t } = useTranslation();

    return (
        <AppShell
            variant="header"
            className="h-dvh min-h-0 overflow-hidden pr-[env(safe-area-inset-right)] pl-[env(safe-area-inset-left)]"
        >
            <SkipToContent />
            {/* Fixed chrome — content below scrolls inside panes, not the page. */}
            <header className="z-20 shrink-0 border-b bg-background/95 pt-[env(safe-area-inset-top)] backdrop-blur-sm">
                <div className="mx-auto flex h-14 w-full max-w-[100rem] items-center gap-3 px-4 md:px-6">
                    <Link
                        href={backHref}
                        className="inline-flex shrink-0 items-center gap-1.5 rounded-md px-1.5 py-1.5 text-sm text-muted-foreground/80 transition-colors hover:bg-muted/60 hover:text-muted-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                    >
                        <ArrowLeft className="size-4" aria-hidden="true" />
                        {t('Back to dossier')}
                    </Link>
                    <div className="min-w-0 flex-1">
                        <h1 className="truncate text-sm font-semibold tracking-tight text-pretty">
                            {title}
                        </h1>
                    </div>
                    {status ? <DossierStatusBadge status={status} /> : null}
                </div>
            </header>
            <AppContent
                id="main-content"
                variant="header"
                className="mx-auto flex min-h-0 w-full max-w-[100rem] flex-1 flex-col overflow-hidden pb-[env(safe-area-inset-bottom)]"
                tabIndex={-1}
            >
                {children}
            </AppContent>
        </AppShell>
    );
}
