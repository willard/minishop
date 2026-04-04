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
    Ticket,
    Truck,
    UserCog,
    Users,
    Percent,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { index as activityLogIndex } from '@/actions/App/Http/Controllers/Admin/ActivityLogController';
import { index as categoriesIndex } from '@/actions/App/Http/Controllers/Admin/CategoryController';
import { index as couponsIndex } from '@/actions/App/Http/Controllers/Admin/CouponController';
import { index as customersIndex } from '@/actions/App/Http/Controllers/Admin/CustomerController';
import { index as ordersIndex } from '@/actions/App/Http/Controllers/Admin/OrderController';
import { index as productsIndex } from '@/actions/App/Http/Controllers/Admin/ProductController';
import { index as returnsIndex } from '@/actions/App/Http/Controllers/Admin/ReturnController';
import { index as shippingMethodsIndex } from '@/actions/App/Http/Controllers/Admin/ShippingMethodController';
import { edit as settingsEdit } from '@/actions/App/Http/Controllers/Admin/StoreSettingsController';
import { index as taxZonesIndex } from '@/actions/App/Http/Controllers/Admin/TaxZoneController';
import { index as usersIndex } from '@/actions/App/Http/Controllers/Admin/UserController';
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
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import AppLogo from './AppLogo.vue';

const { can } = useCan();

type PermissionNavItem = NavItem & { permission?: string };

const allNavItems: PermissionNavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
        permission: 'dashboard.view',
    },
    {
        title: 'Products',
        href: productsIndex().url,
        icon: Package,
        permission: 'products.view',
    },
    {
        title: 'Categories',
        href: categoriesIndex().url,
        icon: Tag,
        permission: 'categories.view',
    },
    {
        title: 'Orders',
        href: ordersIndex().url,
        icon: ShoppingCart,
        permission: 'orders.view',
    },
    {
        title: 'Returns',
        href: returnsIndex().url,
        icon: RotateCcw,
        permission: 'returns.view',
    },
    {
        title: 'Customers',
        href: customersIndex().url,
        icon: Users,
        permission: 'customers.view',
    },
    {
        title: 'Users',
        href: usersIndex().url,
        icon: UserCog,
        permission: 'users.view',
    },
    {
        title: 'Coupons',
        href: couponsIndex().url,
        icon: Ticket,
        permission: 'coupons.view',
    },
    {
        title: 'Shipping Methods',
        href: shippingMethodsIndex().url,
        icon: Truck,
        permission: 'shipping-methods.view',
    },
    {
        title: 'Tax Zones',
        href: taxZonesIndex().url,
        icon: Percent,
        permission: 'tax-zones.view',
    },
    {
        title: 'Activity Log',
        href: activityLogIndex().url,
        icon: ClipboardList,
        permission: 'activity-log.view',
    },
    {
        title: 'Settings',
        href: settingsEdit().url,
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
