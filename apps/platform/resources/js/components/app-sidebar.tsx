import { Link, usePage } from '@inertiajs/react';
import {
    FolderGit2,
    LayoutGrid,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { WorkspaceSwitcher } from '@/components/workspace-switcher';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as clientIndex } from '@/routes/workspaces/clients';
import { index as dossierIndex } from '@/routes/workspaces/dossiers';
import type { NavItem } from '@/types';

export function AppSidebar() {
    // Shared from HandleInertiaRequests — present when a firm is active.
    const { current_firm: currentFirm } = usePage().props;

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        ...(currentFirm
            ? [
                  {
                      title: 'Dossiers',
                      href: dossierIndex(),
                      icon: FolderGit2,
                  },
                  {
                      title: 'Clients',
                      href: clientIndex(),
                      icon: Users,
                  },
              ]
            : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <WorkspaceSwitcher />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
