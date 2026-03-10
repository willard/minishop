import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import DashboardPage from '@/pages/Dashboard.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: {
        name: 'Link',
        template: '<a href="#"><slot /></a>',
        props: ['href'],
    },
}));

vi.mock('@/layouts/AppLayout.vue', () => ({
    default: {
        name: 'AppLayout',
        template: '<div><slot /></div>',
        props: ['breadcrumbs'],
    },
}));

vi.mock('@/components/ui/badge', () => ({
    Badge: {
        name: 'Badge',
        template: '<span class="badge"><slot /></span>',
        props: ['variant'],
    },
}));

vi.mock('@/components/ui/button', () => ({
    Button: {
        name: 'Button',
        template: '<button><slot /></button>',
        props: ['variant', 'size'],
    },
}));

vi.mock('@/routes', () => ({
    dashboard: vi.fn(() => ({ url: '/dashboard' })),
}));

vi.mock('@/actions/App/Http/Controllers/Admin/OrderController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/orders' })),
    show: vi.fn((order: { order_number: string }) => ({
        url: `/dashboard/orders/${order.order_number}`,
    })),
}));

vi.mock('@/actions/App/Http/Controllers/Admin/ProductController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/products' })),
}));

const baseProps = {
    totalRevenue: 152300,
    totalOrders: 8,
    totalCustomers: 3,
    lowStockCount: 2,
    recentOrders: [
        {
            id: 1,
            order_number: 'ORD-000001',
            status: 'pending',
            total_amount: 5000,
            customer: {
                id: 1,
                user: { id: 2, name: 'Jane Doe', email: 'jane@example.com' },
            },
            created_at: '2026-02-23T10:00:00.000Z',
        },
        {
            id: 2,
            order_number: 'ORD-000002',
            status: 'delivered',
            total_amount: 12000,
            customer: {
                id: 2,
                user: { id: 3, name: 'John Smith', email: 'john@example.com' },
            },
            created_at: '2026-02-22T10:00:00.000Z',
        },
    ],
    lowStockProducts: [
        { id: 1, name: 'Blue T-Shirt', sku: 'TSH-001', stock_quantity: 3 },
        { id: 2, name: 'Red Cap', sku: null, stock_quantity: 8 },
    ],
};

describe('Dashboard', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        wrapper = mount(DashboardPage, { props: baseProps });
    });

    it('renders without errors', () => {
        expect(wrapper.exists()).toBe(true);
    });

    it('displays formatted total revenue', () => {
        expect(wrapper.text()).toContain('1,523.00');
    });

    it('displays total orders count', () => {
        expect(wrapper.text()).toContain('8');
    });

    it('displays total customers count', () => {
        expect(wrapper.text()).toContain('3');
    });

    it('displays low stock count', () => {
        expect(wrapper.text()).toContain('2');
    });

    it('renders recent order rows', () => {
        expect(wrapper.text()).toContain('ORD-000001');
        expect(wrapper.text()).toContain('ORD-000002');
        expect(wrapper.text()).toContain('Jane Doe');
        expect(wrapper.text()).toContain('John Smith');
    });

    it('renders low stock product rows', () => {
        expect(wrapper.text()).toContain('Blue T-Shirt');
        expect(wrapper.text()).toContain('Red Cap');
        expect(wrapper.text()).toContain('3 left');
        expect(wrapper.text()).toContain('8 left');
    });

    it('shows empty state for recent orders when list is empty', () => {
        const emptyWrapper = mount(DashboardPage, {
            props: { ...baseProps, recentOrders: [] },
        });
        expect(emptyWrapper.text()).toContain('No orders yet');
    });

    it('shows well stocked message when no low stock products', () => {
        const emptyWrapper = mount(DashboardPage, {
            props: { ...baseProps, lowStockProducts: [] },
        });
        expect(emptyWrapper.text()).toContain('All products are well stocked');
    });
});
