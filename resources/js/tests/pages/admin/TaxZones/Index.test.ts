import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import TaxZonesIndexPage from '@/pages/admin/TaxZones/Index.vue';

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

vi.mock('@/actions/App/Http/Controllers/Admin/TaxZoneController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/tax-zones' })),
    create: vi.fn(() => ({ url: '/dashboard/tax-zones/create' })),
    edit: vi.fn((id: number) => ({ url: `/dashboard/tax-zones/${id}/edit` })),
    destroy: vi.fn((id: number) => ({ url: `/dashboard/tax-zones/${id}` })),
}));

const baseTaxZones = {
    data: [
        {
            id: 1,
            name: 'Ontario',
            country_code: 'CA',
            province_code: 'ON',
            is_active: true,
            priority: 10,
            rates: [
                { id: 1, name: 'HST', name_fr: 'TVH', rate: 13, is_compound: false, sort_order: 1 },
            ],
        },
        {
            id: 2,
            name: 'Quebec',
            country_code: 'CA',
            province_code: 'QC',
            is_active: false,
            priority: 10,
            rates: [],
        },
    ],
    links: [],
};

describe('admin/TaxZones/Index', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        wrapper = mount(TaxZonesIndexPage, {
            props: { taxZones: baseTaxZones },
        });
    });

    it('renders without errors', () => {
        expect(wrapper.exists()).toBe(true);
    });

    it('displays the page heading', () => {
        expect(wrapper.text()).toContain('Tax Zones');
    });

    it('renders a row for each tax zone', () => {
        const rows = wrapper.findAll('tbody tr');
        expect(rows.length).toBeGreaterThanOrEqual(2);
    });

    it('displays zone names', () => {
        expect(wrapper.text()).toContain('Ontario');
        expect(wrapper.text()).toContain('Quebec');
    });

    it('displays province codes', () => {
        expect(wrapper.text()).toContain('ON');
        expect(wrapper.text()).toContain('QC');
    });

    it('shows Active badge for active zones', () => {
        expect(wrapper.text()).toContain('Active');
    });

    it('shows Inactive badge for inactive zones', () => {
        expect(wrapper.text()).toContain('Inactive');
    });

    it('shows rate count for each zone', () => {
        expect(wrapper.text()).toContain('1 rate');
    });

    it('shows New Zone button', () => {
        expect(wrapper.text()).toContain('New Zone');
    });

    it('shows empty state when no zones exist', () => {
        const emptyWrapper = mount(TaxZonesIndexPage, {
            props: { taxZones: { data: [], links: [] } },
        });
        expect(emptyWrapper.text()).toContain('No tax zones configured yet');
    });
});
