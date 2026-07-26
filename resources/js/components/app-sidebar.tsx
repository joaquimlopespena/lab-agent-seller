import { Link } from '@inertiajs/react';
import { DatabaseIcon, MessagesSquareIcon, PlugIcon } from 'lucide-react';
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
import { index as conversationsIndex } from '@/routes/admin/conversations';
import { index as integrationsIndex } from '@/routes/admin/integrations';
import { index as syncedDataIndex } from '@/routes/admin/synced-data';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Integrações',
        href: integrationsIndex(),
        icon: PlugIcon,
    },
    {
        title: 'Dados sincronizados',
        href: syncedDataIndex(),
        icon: DatabaseIcon,
    },
    {
        title: 'Conversas',
        href: conversationsIndex(),
        icon: MessagesSquareIcon,
    },
];

export function AppSidebar() {
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
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
