<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpen, ClipboardList, Folder, LayoutGrid, Package, Settings, ShoppingCart, Tag, Ticket, Truck, Users } from 'lucide-vue-next';
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
import { type NavItem } from '@/types';
import AppLogo from './AppLogo.vue';
import { dashboard } from '@/routes';
import { index as productsIndex } from '@/actions/App/Http/Controllers/Admin/ProductController';
import { index as categoriesIndex } from '@/actions/App/Http/Controllers/Admin/CategoryController';
import { index as ordersIndex } from '@/actions/App/Http/Controllers/Admin/OrderController';
import { index as customersIndex } from '@/actions/App/Http/Controllers/Admin/CustomerController';
import { index as couponsIndex } from '@/actions/App/Http/Controllers/Admin/CouponController';
import { index as shippingMethodsIndex } from '@/actions/App/Http/Controllers/Admin/ShippingMethodController';
import { edit as settingsEdit } from '@/actions/App/Http/Controllers/Admin/StoreSettingsController';
import { index as activityLogIndex } from '@/actions/App/Http/Controllers/Admin/ActivityLogController';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Products',
        href: productsIndex().url,
        icon: Package,
    },
    {
        title: 'Categories',
        href: categoriesIndex().url,
        icon: Tag,
    },
    {
        title: 'Orders',
        href: ordersIndex().url,
        icon: ShoppingCart,
    },
    {
        title: 'Customers',
        href: customersIndex().url,
        icon: Users,
    },
    {
        title: 'Coupons',
        href: couponsIndex().url,
        icon: Ticket,
    },
    {
        title: 'Shipping Methods',
        href: shippingMethodsIndex().url,
        icon: Truck,
    },
    {
        title: 'Activity Log',
        href: activityLogIndex().url,
        icon: ClipboardList,
    },
    {
        title: 'Settings',
        href: settingsEdit().url,
        icon: Settings,
    },
];

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
                        <Link :href="dashboard()">
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
