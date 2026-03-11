import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import IndexPage from '@/pages/admin/Orders/Index.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: {
        name: 'Link',
        template: '<a href="#"><slot /></a>',
        props: ['href'],
    },
    router: { delete: vi.fn(), get: vi.fn() },
}));

vi.mock('@/layouts/AppLayout.vue', () => ({
    default: {
        name: 'AppLayout',
        template: '<div><slot /></div>',
        props: ['breadcrumbs'],
    },
}));

vi.mock('@/components/ui/button', () => ({
    Button: {
        name: 'Button',
        template: '<button><slot /></button>',
        props: ['variant', 'size', 'type', 'disabled'],
    },
}));

vi.mock('@/components/ui/input', () => ({
    Input: {
        name: 'Input',
        template: '<input />',
        props: ['modelValue', 'placeholder', 'class'],
    },
}));

vi.mock('@/actions/App/Http/Controllers/Admin/OrderController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/orders' })),
    create: vi.fn(() => ({ url: '/dashboard/orders/create' })),
    show: vi.fn((order: { order_number: string }) => ({
        url: `/dashboard/orders/${order.order_number}`,
    })),
    destroy: vi.fn((order: { order_number: string }) => ({
        url: `/dashboard/orders/${order.order_number}`,
    })),
}));

const baseOrders = {
    data: [
        {
            id: 1,
            order_number: 'ORD-000001',
            status: 'pending',
            total_amount: 5000,
            customer: {
                id: 1,
                user: { id: 2, name: 'Jane Doe', email: 'jane@example.com' },
            },
            items_count: 2,
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
            items_count: 1,
            created_at: '2026-02-22T10:00:00.000Z',
        },
        {
            id: 3,
            order_number: 'ORD-000003',
            status: 'cancelled',
            total_amount: 3000,
            customer: {
                id: 3,
                user: { id: 4, name: 'Alex Lee', email: 'alex@example.com' },
            },
            items_count: 1,
            created_at: '2026-02-21T10:00:00.000Z',
        },
    ],
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 3,
    links: [],
};

const baseFilters: {
    status?: string;
    search?: string;
    sort_by?: string;
    sort_dir?: string;
} = {
    status: undefined,
    search: '',
    sort_by: undefined,
    sort_dir: undefined,
};

const baseStatuses = [
    { value: 'pending', label: 'Pending' },
    { value: 'processing', label: 'Processing' },
    { value: 'shipped', label: 'Shipped' },
    { value: 'delivered', label: 'Delivered' },
    { value: 'cancelled', label: 'Cancelled' },
    { value: 'refunded', label: 'Refunded' },
];

describe('admin/Orders/Index', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        wrapper = mount(IndexPage, {
            props: {
                orders: baseOrders,
                filters: baseFilters,
                statuses: baseStatuses,
            },
        });
    });

    it('renders without errors', () => {
        expect(wrapper.exists()).toBe(true);
    });

    it('displays the total orders count', () => {
        expect(wrapper.text()).toContain('3 total orders');
    });

    it('displays order numbers', () => {
        expect(wrapper.text()).toContain('ORD-000001');
        expect(wrapper.text()).toContain('ORD-000002');
    });

    it('displays customer names', () => {
        expect(wrapper.text()).toContain('Jane Doe');
        expect(wrapper.text()).toContain('John Smith');
    });

    it('displays formatted totals', () => {
        expect(wrapper.text()).toContain('50.00');
        expect(wrapper.text()).toContain('120.00');
    });

    it('renders the search input', () => {
        expect(wrapper.find('input').exists()).toBe(true);
    });

    it('renders the New Order button', () => {
        expect(wrapper.text()).toContain('New Order');
    });


    it('renders status filter as a select dropdown with all status options', () => {
        const select = wrapper.find('select');
        expect(select.exists()).toBe(true);
        const options = select.findAll('option');
        const optionTexts = options.map((o) => o.text().trim());
        expect(optionTexts).toContain('All Statuses');
        baseStatuses.forEach((s) => {
            expect(optionTexts).toContain(s.label);
        });
    });

    it('renders sortable column headers for order number, total, status, and date', () => {
        const sortButtons = wrapper.findAll('thead button');
        const texts = sortButtons.map((b) => b.text().trim().toLowerCase());
        expect(texts.some((t) => t.includes('order'))).toBe(true);
        expect(texts.some((t) => t.includes('total'))).toBe(true);
        expect(texts.some((t) => t.includes('status'))).toBe(true);
        expect(texts.some((t) => t.includes('date'))).toBe(true);
    });

    it('clicking a sort header calls router.get with sort_by and sort_dir', async () => {
        const { router } = await import('@inertiajs/vue3');
        const sortButtons = wrapper.findAll('thead button');
        await sortButtons[0].trigger('click');
        expect(router.get).toHaveBeenCalledWith(
            '/dashboard/orders',
            expect.objectContaining({ sort_by: expect.any(String), sort_dir: 'asc' }),
            expect.any(Object),
        );
    });

    it('applies status-specific color classes to status badges', () => {
        const html = wrapper.html();
        expect(html).toContain('bg-amber-100');    // pending
        expect(html).toContain('bg-emerald-100');  // delivered
        expect(html).toContain('bg-red-100');      // cancelled
    });

    it('shows empty state when no orders', () => {
        const emptyWrapper = mount(IndexPage, {
            props: {
                orders: { ...baseOrders, data: [], total: 0 },
                filters: baseFilters,
                statuses: baseStatuses,
            },
        });
        expect(emptyWrapper.text()).toContain('No orders yet');
    });

    it('shows "No orders found." when empty and a filter is active', () => {
        const filteredWrapper = mount(IndexPage, {
            props: {
                orders: { ...baseOrders, data: [], total: 0 },
                filters: { status: 'pending', search: '' },
                statuses: baseStatuses,
            },
        });
        expect(filteredWrapper.text()).toContain('No orders found.');
    });
});
