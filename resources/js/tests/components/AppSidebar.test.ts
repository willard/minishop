import AppSidebar from '@/components/AppSidebar.vue';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const mockCan = vi.fn(() => true);

vi.mock('@/composables/useCan', () => ({
    useCan: () => ({ can: mockCan, hasRole: vi.fn(() => false) }),
}));

vi.mock('@inertiajs/vue3', () => ({
    usePage: vi.fn(() => ({
        props: {
            auth: { user: { id: 1, name: 'Test', email: 'test@example.com' }, roles: [], permissions: [] },
        },
    })),
    Link: { name: 'Link', template: '<a><slot /></a>', props: ['href'] },
}));

vi.mock('@/routes', () => ({
    dashboard: vi.fn(() => '/dashboard'),
}));

vi.mock('@/actions/App/Http/Controllers/Admin/ProductController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/products' })),
}));
vi.mock('@/actions/App/Http/Controllers/Admin/CategoryController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/categories' })),
}));
vi.mock('@/actions/App/Http/Controllers/Admin/OrderController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/orders' })),
}));
vi.mock('@/actions/App/Http/Controllers/Admin/CustomerController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/customers' })),
}));
vi.mock('@/actions/App/Http/Controllers/Admin/CouponController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/coupons' })),
}));
vi.mock('@/actions/App/Http/Controllers/Admin/ShippingMethodController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/shipping-methods' })),
}));
vi.mock('@/actions/App/Http/Controllers/Admin/StoreSettingsController', () => ({
    edit: vi.fn(() => ({ url: '/dashboard/settings' })),
}));
vi.mock('@/actions/App/Http/Controllers/Admin/ActivityLogController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/activity-log' })),
}));

vi.mock('@/components/ui/sidebar', () => ({
    Sidebar: { name: 'Sidebar', template: '<div><slot /></div>', props: ['collapsible', 'variant'] },
    SidebarContent: { name: 'SidebarContent', template: '<div><slot /></div>' },
    SidebarFooter: { name: 'SidebarFooter', template: '<div><slot /></div>' },
    SidebarHeader: { name: 'SidebarHeader', template: '<div><slot /></div>' },
    SidebarMenu: { name: 'SidebarMenu', template: '<div><slot /></div>' },
    SidebarMenuButton: { name: 'SidebarMenuButton', template: '<div><slot /></div>', props: ['size', 'asChild'] },
    SidebarMenuItem: { name: 'SidebarMenuItem', template: '<div><slot /></div>' },
}));

vi.mock('@/components/NavMain.vue', () => ({
    default: {
        name: 'NavMain',
        template: '<div class="nav-main"><span v-for="item in items" :key="item.title" class="nav-item">{{ item.title }}</span></div>',
        props: ['items'],
    },
}));

vi.mock('@/components/NavFooter.vue', () => ({
    default: { name: 'NavFooter', template: '<div />', props: ['items'] },
}));

vi.mock('@/components/NavUser.vue', () => ({
    default: { name: 'NavUser', template: '<div />' },
}));

vi.mock('@/components/AppLogo.vue', () => ({
    default: { name: 'AppLogo', template: '<div>Logo</div>' },
}));

const ALL_NAV_TITLES = [
    'Dashboard',
    'Products',
    'Categories',
    'Orders',
    'Customers',
    'Coupons',
    'Shipping Methods',
    'Activity Log',
    'Settings',
];

const MANAGER_PERMISSIONS = [
    'dashboard.view',
    'products.view',
    'categories.view',
    'orders.view',
    'customers.view',
];

function getNavItemTitles(wrapper: ReturnType<typeof mount>) {
    return wrapper.findAll('.nav-item').map((el) => el.text());
}

describe('AppSidebar', () => {
    beforeEach(() => {
        mockCan.mockReset();
    });

    it('shows all nav items when user has all permissions', () => {
        mockCan.mockReturnValue(true);

        const wrapper = mount(AppSidebar);
        const titles = getNavItemTitles(wrapper);

        ALL_NAV_TITLES.forEach((title) => {
            expect(titles).toContain(title);
        });
        expect(titles).toHaveLength(ALL_NAV_TITLES.length);
    });

    it('hides settings for admin role', () => {
        mockCan.mockImplementation((permission: string) => {
            return permission !== 'settings.view';
        });

        const wrapper = mount(AppSidebar);
        const titles = getNavItemTitles(wrapper);

        expect(titles).not.toContain('Settings');
        expect(titles).toContain('Dashboard');
        expect(titles).toContain('Products');
        expect(titles).toContain('Activity Log');
    });

    it('shows only permitted items for manager role', () => {
        mockCan.mockImplementation((permission: string) => {
            return MANAGER_PERMISSIONS.includes(permission);
        });

        const wrapper = mount(AppSidebar);
        const titles = getNavItemTitles(wrapper);

        expect(titles).toContain('Dashboard');
        expect(titles).toContain('Products');
        expect(titles).toContain('Categories');
        expect(titles).toContain('Orders');
        expect(titles).toContain('Customers');

        expect(titles).not.toContain('Coupons');
        expect(titles).not.toContain('Shipping Methods');
        expect(titles).not.toContain('Activity Log');
        expect(titles).not.toContain('Settings');
    });
});
