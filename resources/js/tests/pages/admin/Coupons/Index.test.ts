import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import CouponsIndexPage from '@/pages/admin/Coupons/Index.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: {
        name: 'Link',
        template: '<a :href="href"><slot /></a>',
        props: ['href'],
    },
    router: { delete: vi.fn() },
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
        template: '<button @click="$emit(\'click\')"><slot /></button>',
        props: ['variant', 'size', 'type'],
    },
}));

vi.mock('@/components/ui/badge', () => ({
    Badge: {
        name: 'Badge',
        template: '<span><slot /></span>',
        props: ['variant'],
    },
}));

vi.mock('@/actions/App/Http/Controllers/Admin/CouponController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/coupons' })),
    create: vi.fn(() => ({ url: '/dashboard/coupons/create' })),
    edit: vi.fn((c: { id: number }) => ({
        url: `/dashboard/coupons/${c.id}/edit`,
    })),
    destroy: vi.fn((c: { id: number }) => ({
        url: `/dashboard/coupons/${c.id}`,
    })),
}));

const basePagination = {
    data: [
        {
            id: 1,
            code: 'SAVE10',
            description: '10% off',
            type: 'percentage' as const,
            value: 10,
            minimum_order_amount: null,
            expiry_date: null,
            usage_limit: null,
            used_count: 3,
            is_active: true,
        },
        {
            id: 2,
            code: 'FLAT50',
            description: 'Fixed discount',
            type: 'fixed' as const,
            value: 5000,
            minimum_order_amount: 20000,
            expiry_date: '2027-12-31',
            usage_limit: 100,
            used_count: 10,
            is_active: false,
        },
    ],
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 2,
    links: [],
};

describe('admin/Coupons/Index', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        wrapper = mount(CouponsIndexPage, {
            props: { coupons: basePagination },
        });
    });

    it('renders without errors', () => {
        expect(wrapper.exists()).toBe(true);
    });

    it('displays the page title', () => {
        expect(wrapper.text()).toContain('Coupons');
    });

    it('displays the total coupon count', () => {
        expect(wrapper.text()).toContain('2 total coupons');
    });

    it('renders a row for each coupon', () => {
        const rows = wrapper.findAll('tbody tr');
        expect(rows).toHaveLength(2);
    });

    it('displays coupon codes', () => {
        expect(wrapper.text()).toContain('SAVE10');
        expect(wrapper.text()).toContain('FLAT50');
    });

    it('formats percentage value correctly', () => {
        expect(wrapper.text()).toContain('10%');
    });

    it('formats fixed value correctly', () => {
        expect(wrapper.text()).toContain('₱50.00');
    });

    it('shows an empty state when there are no coupons', () => {
        const emptyWrapper = mount(CouponsIndexPage, {
            props: {
                coupons: { ...basePagination, data: [], total: 0 },
            },
        });
        expect(emptyWrapper.text()).toContain('No coupons yet');
    });

    it('shows the Add Coupon button', () => {
        expect(wrapper.text()).toContain('Add Coupon');
    });
});
