import { Deferred } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { AppContent } from '@/components/app-shell/app-content';
import { AppShell } from '@/components/app-shell/app-shell';
import { AppSidebar } from '@/components/app-shell/app-sidebar';
import { AppSidebarHeader } from '@/components/app-shell/app-sidebar-header';
import { Button } from '@/components/ui/button';
import NotificationBell from '@/features/notifications/notification-bell';
import { WorkspaceNotifications } from '@/hooks/use-workspace-notifications';
import type { AppLayoutProps } from '@/types';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    return (
        <AppShell variant="sidebar">
            {/* Real-time inbox refresh when a client finishes a questionnaire. */}
            <WorkspaceNotifications />
            <AppSidebar />
            <AppContent variant="sidebar" className="overflow-x-hidden">
                <AppSidebarHeader
                    breadcrumbs={breadcrumbs}
                    actions={
                        <Deferred
                            data="notifications"
                            fallback={
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="relative size-9"
                                    disabled
                                    aria-label="Notifications"
                                >
                                    <Bell className="size-5 opacity-50" />
                                </Button>
                            }
                        >
                            <NotificationBell />
                        </Deferred>
                    }
                />
                {children}
            </AppContent>
        </AppShell>
    );
}
