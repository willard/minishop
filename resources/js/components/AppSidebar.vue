<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BookOpen,
    ClipboardList,
    Folder,
    LayoutGrid,
    Package,
    RotateCcw,
    Settings,
    ShoppingCart,
    Tag,
    Tags,
    Ticket,
    Truck,
    UserCog,
    Users,
    Percent,
} from 'lucide-vue-next';
import { computed } from 'vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCan } from '@/composables/useCan';

import { type NavItem } from '@/types';
import AppLogo from './AppLogo.vue';

const { can } = useCan();

type PermissionNavItem = NavItem & { permission?: string };

const allNavItems: PermissionNavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
        permission: 'dashboard.view',
    },
    {
        title: 'Products',
        href: '/dashboard/products',
        icon: Package,
        permission: 'products.view',
    },
    {
        title: 'Categories',
        href: '/dashboard/categories',
        icon: Tag,
        permission: 'categories.view',
    },
    {
        title: 'Tags',
        href: '/dashboard/tags',
        icon: Tags,
        permission: 'tags.view',
    },
    {
        title: 'Orders',
        href: '/dashboard/orders',
        icon: ShoppingCart,
        permission: 'orders.view',
    },
    {
        title: 'Returns',
        href: '/dashboard/order-returns',
        icon: RotateCcw,
        permission: 'returns.view',
    },
    {
        title: 'Customers',
        href: '/dashboard/customers',
        icon: Users,
        permission: 'customers.view',
    },
    {
        title: 'Users',
        href: '/dashboard/users',
        icon: UserCog,
        permission: 'users.view',
    },
    {
        title: 'Coupons',
        href: '/dashboard/coupons',
        icon: Ticket,
        permission: 'coupons.view',
    },
    {
        title: 'Shipping Methods',
        href: '/dashboard/shipping-methods',
        icon: Truck,
        permission: 'shipping-methods.view',
    },
    {
        title: 'Tax Zones',
        href: '/dashboard/tax-zones',
        icon: Percent,
        permission: 'tax-zones.view',
    },
    {
        title: 'Activity Log',
        href: '/dashboard/activity-logs',
        icon: ClipboardList,
        permission: 'activity-log.view',
    },
    {
        title: 'Settings',
        href: '/dashboard',
        icon: Settings,
        permission: 'settings.view',
    },
];

const mainNavItems = computed<NavItem[]>(() =>
    allNavItems.filter((item) => !item.permission || can(item.permission)),
);

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
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
                        <Link href="/dashboard">
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
