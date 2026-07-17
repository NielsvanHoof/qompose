import { Link, usePage } from '@inertiajs/react';
import {
    ClipboardList,
    FolderGit2,
    Images,
    LayoutGrid,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-shell/app-logo';
import { NavMain } from '@/components/app-shell/nav-main';
import { NavUser } from '@/components/app-shell/nav-user';
import { WorkspaceSwitcher } from '@/components/app-shell/workspace-switcher';
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
import { dashboard as workspaceDashboard } from '@/routes/workspaces';
import { index as clientIndex } from '@/routes/workspaces/clients';
import { index as dossierIndex } from '@/routes/workspaces/dossiers';
import { index as mediaIndex } from '@/routes/workspaces/media';
import { index as templateIndex } from '@/routes/workspaces/templates';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const { current_firm: currentFirm } = usePage().props;
    const dashboardRoute = currentFirm
        ? workspaceDashboard(currentFirm)
        : dashboard();

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboardRoute,
            icon: LayoutGrid,
        },
        ...(currentFirm
            ? [
                  {
                      title: 'Dossiers',
                      href: dossierIndex(currentFirm),
                      icon: FolderGit2,
                  },
                  {
                      title: 'Clients',
                      href: clientIndex(currentFirm),
                      icon: Users,
                  },
                  {
                      title: 'Templates',
                      href: templateIndex(currentFirm),
                      icon: ClipboardList,
                  },
                  {
                      title: 'Media Library',
                      href: mediaIndex(currentFirm),
                      icon: Images,
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
                            <Link href={dashboardRoute} prefetch>
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
