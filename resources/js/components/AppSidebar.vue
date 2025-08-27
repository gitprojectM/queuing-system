<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';

import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

// Add adminOnly property to admin-only items
const allNavItems: (NavItem & { adminOnly?: boolean })[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
        adminOnly: true,
    },
    {
        title: 'User Dashboard',
        href: '/user',
        icon: LayoutGrid,
    },
    {
        title: 'My Queue',
        href: '/my-queue',
        icon: BookOpen,
    },
    {
        title: 'Queue Register',
        href: '/queue/register',
        icon: BookOpen,
    },
    {
        title: 'Queue Monitoring',
        href: '/queue/monitor',
        icon: BookOpen,
    },
    {
        title: 'Queue List',
        href: '/queue/list',
        icon: BookOpen,
    },
    {
        title: 'Users',
        href: '/users',
        icon: BookOpen,
        adminOnly: true,
    },
    {
        title: 'User Assignment',
        href: '/users/assign',
        icon: BookOpen,
        adminOnly: true,
    },
    {
        title: 'Services',
        href: '/services',
        icon: Folder,
        adminOnly: true,
    },
    {
        title: 'Window',
        href: '/windows',
        icon: LayoutGrid,
        adminOnly: true,
    },
];

const page = usePage();
const user = page.props.auth?.user;
// Assume user.role is provided as a string ('admin' or 'user') from backend
const isAdmin = user && user.role === 'admin';
const mainNavItems: NavItem[] = allNavItems.filter(item => {
    if (item.adminOnly) {
        return isAdmin;
    }
    return true;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/tampco/queuing-system',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://tampco.com/docs/queuing-system',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
