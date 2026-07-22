import { router, usePage } from '@inertiajs/react';
import { Building2, Check, ChevronsUpDown, Plus } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useTranslation } from '@/hooks/use-translation';
import { create as createFirm } from '@/routes/firms';
import { dashboard as workspaceDashboard } from '@/routes/workspaces';

export function WorkspaceSwitcher() {
    const { current_firm: currentFirm, workspaces = [] } = usePage().props;
    const { t } = useTranslation();

    if (!currentFirm) {
        return null;
    }

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            className="group text-sidebar-accent-foreground data-[state=open]:bg-sidebar-accent"
                        >
                            <Building2 className="size-4" />
                            <span className="truncate font-medium">
                                {currentFirm.name}
                            </span>
                            <ChevronsUpDown className="ml-auto size-4" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                        align="end"
                        side="top"
                    >
                        <DropdownMenuLabel>{t('Your firms')}</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        {workspaces.map((firm) => (
                            <DropdownMenuItem
                                key={firm.slug}
                                onSelect={() => {
                                    if (firm.slug !== currentFirm.slug) {
                                        router.visit(
                                            workspaceDashboard.url(firm),
                                        );
                                    }
                                }}
                            >
                                <Building2 />
                                <span className="truncate">{firm.name}</span>
                                {firm.slug === currentFirm.slug && (
                                    <Check className="ml-auto" />
                                )}
                            </DropdownMenuItem>
                        ))}
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            onSelect={() => router.visit(createFirm.url())}
                        >
                            <Plus />
                            <span>{t('Create new firm')}</span>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
