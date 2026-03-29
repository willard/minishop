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
    router: { delete: vi.fn(), get: vi.fn(), post: vi.fn() },
}));

vi.mock('@/actions/App/Http/Controllers/Admin/OrderBulkActionController', () => ({
    default: vi.fn(() => ({ url: '/dashboard/orders/bulk', method: 'post' })),
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

    it('renders the New Order button', () => {
        expect(wrapper.text()).toContain('New Order');
    });

    describe('bulk actions', () => {
        it('renders a checkbox in the table header for select-all', () => {
            const headerCheckbox = wrapper.find('thead input[type="checkbox"]');
            expect(headerCheckbox.exists()).toBe(true);
        });

        it('renders a checkbox for each order row', () => {
            const rowCheckboxes = wrapper.findAll('tbody input[type="checkbox"]');
            expect(rowCheckboxes).toHaveLength(baseOrders.data.length);
        });

        it('bulk action toolbar is hidden when no orders are selected', () => {
            expect(wrapper.text()).not.toContain('selected');
            expect(wrapper.text()).not.toContain('Update Status');
        });

        it('bulk action toolbar appears after selecting an order', async () => {
            const checkbox = wrapper.find('tbody input[type="checkbox"]');
            await checkbox.trigger('change');
            expect(wrapper.text()).toContain('1 order selected');
            expect(wrapper.text()).toContain('Update Status');
            expect(wrapper.text()).toContain('Delete');
        });

        it('shows correct count when multiple orders are selected', async () => {
            const checkboxes = wrapper.findAll('tbody input[type="checkbox"]');
            await checkboxes[0].trigger('change');
            await checkboxes[1].trigger('change');
            expect(wrapper.text()).toContain('2 orders selected');
        });

        it('select-all checkbox selects all orders on the page', async () => {
            const headerCheckbox = wrapper.find('thead input[type="checkbox"]');
            await headerCheckbox.trigger('change');
            expect(wrapper.text()).toContain(`${baseOrders.data.length} orders selected`);
        });

        it('clicking Clear deselects all and hides the toolbar', async () => {
            const checkbox = wrapper.find('tbody input[type="checkbox"]');
            await checkbox.trigger('change');
            expect(wrapper.text()).toContain('1 order selected');

            const clearButton = wrapper.findAll('button').find((b) => b.text().trim() === 'Clear');
            await clearButton!.trigger('click');
            expect(wrapper.text()).not.toContain('selected');
        });

        it('clicking Update Status opens the status modal', async () => {
            const checkbox = wrapper.find('tbody input[type="checkbox"]');
            await checkbox.trigger('change');

            const updateStatusButton = wrapper.findAll('button').find((b) => b.text().trim() === 'Update Status');
            await updateStatusButton!.trigger('click');

            expect(wrapper.text()).toContain('Update Order Status');
        });

        it('status modal contains all status options', async () => {
            const checkbox = wrapper.find('tbody input[type="checkbox"]');
            await checkbox.trigger('change');

            const updateStatusButton = wrapper.findAll('button').find((b) => b.text().trim() === 'Update Status');
            await updateStatusButton!.trigger('click');

            const modalSelects = wrapper.findAll('select');
            const modalSelect = modalSelects[modalSelects.length - 1];
            const optionTexts = modalSelect.findAll('option').map((o) => o.text().trim());
            baseStatuses.forEach((s) => {
                expect(optionTexts).toContain(s.label);
            });
        });

        it('selected row is highlighted', async () => {
            const rows = wrapper.findAll('tbody tr');
            await rows[0].find('input[type="checkbox"]').trigger('change');
            expect(rows[0].classes()).toContain('bg-primary/5');
        });
    });
});
