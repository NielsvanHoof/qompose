import { AppContent } from '@/components/app-shell/app-content';
import { AppHeader } from '@/components/app-shell/app-header';
import { AppShell } from '@/components/app-shell/app-shell';
import type { AppLayoutProps } from '@/types';

export default function AppHeaderLayout({
    children,
    breadcrumbs,
}: AppLayoutProps) {
    return (
        <AppShell variant="header">
            <AppHeader breadcrumbs={breadcrumbs} />
            <AppContent variant="header">{children}</AppContent>
        </AppShell>
    );
}
