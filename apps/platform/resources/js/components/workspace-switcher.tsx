import { router, usePage } from '@inertiajs/react';
import { Building2, Check, ChevronsUpDown } from 'lucide-react';
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
import { activate } from '@/routes/firms';

type Firm = {
    name: string;
    slug: string;
};

export function WorkspaceSwitcher() {
    const { current_firm: currentFirm, workspaces = [] } = usePage<{
        current_firm?: Firm | null;
        workspaces?: Firm[];
    }>().props;

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
                        <DropdownMenuLabel>Your firms</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        {workspaces.map((firm) => (
                            <DropdownMenuItem
                                key={firm.slug}
                                onSelect={() => {
                                    if (firm.slug !== currentFirm.slug) {
                                        router.post(activate.url(firm));
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
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
