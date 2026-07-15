import { Link, usePage } from '@inertiajs/react';
import {
    FolderGit2,
    FolderOpen,
    LayoutGrid,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
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
    const { tenant, workspaces = [] } = usePage<{
        tenant?: { slug: string };
        workspaces?: { name: string; slug: string }[];
    }>().props;
    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        ...(tenant
            ? [
                  {
                      title: 'Dossiers',
                      href: dossierIndex(tenant),
                      icon: FolderGit2,
                  },
                  {
                      title: 'Clients',
                      href: clientIndex(tenant),
                      icon: Users,
                  },
              ]
            : []),
    ];
    const workspaceNavItems: NavItem[] = workspaces.map((workspace) => ({
        title: workspace.name,
        href: dossierIndex(workspace),
        icon: FolderOpen,
    }));

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
                {workspaceNavItems.length > 0 && (
                    <NavMain label="Workspaces" items={workspaceNavItems} />
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
