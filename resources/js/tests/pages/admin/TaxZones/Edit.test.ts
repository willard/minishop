import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import EditTaxZonePage from '@/pages/admin/TaxZones/Edit.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    router: { delete: vi.fn() },
    useForm: vi.fn((initial: Record<string, unknown>) => ({
        ...initial,
        errors: {},
        processing: false,
        put: vi.fn(),
        post: vi.fn(),
        reset: vi.fn(),
    })),
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
        template: '<button :disabled="disabled" :type="type"><slot /></button>',
        props: ['variant', 'size', 'type', 'disabled'],
    },
}));

vi.mock('@/components/ui/input', () => ({
    Input: {
        name: 'Input',
        template: '<input />',
        props: ['id', 'modelValue', 'type', 'min', 'placeholder', 'maxlength', 'class'],
    },
}));

vi.mock('@/components/ui/label', () => ({
    Label: {
        name: 'Label',
        template: '<label><slot /></label>',
        props: ['for'],
    },
}));

vi.mock('@/components/InputError.vue', () => ({
    default: { name: 'InputError', template: '<span />', props: ['message'] },
}));

vi.mock('@/actions/App/Http/Controllers/Admin/TaxZoneController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/tax-zones' })),
    edit: vi.fn((id: number) => ({ url: `/dashboard/tax-zones/${id}/edit` })),
    update: vi.fn((id: number) => ({ url: `/dashboard/tax-zones/${id}` })),
}));

vi.mock('@/actions/App/Http/Controllers/Admin/TaxZoneRateController', () => ({
    store: vi.fn((taxZoneId: number) => ({ url: `/dashboard/tax-zones/${taxZoneId}/rates` })),
    update: vi.fn(({ tax_zone, rate }: { tax_zone: number; rate: number }) => ({
        url: `/dashboard/tax-zones/${tax_zone}/rates/${rate}`,
    })),
    destroy: vi.fn(({ tax_zone, rate }: { tax_zone: number; rate: number }) => ({
        url: `/dashboard/tax-zones/${tax_zone}/rates/${rate}`,
    })),
}));

const baseTaxZone = {
    id: 1,
    name: 'Ontario',
    country_code: 'CA',
    province_code: 'ON',
    is_active: true,
    priority: 10,
    rates: [
        {
            id: 1,
            name: 'HST',
            name_fr: 'TVH',
            rate: 13,
            is_compound: false,
            is_shipping_taxable: false,
            sort_order: 1,
        },
    ],
};

describe('admin/TaxZones/Edit', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        wrapper = mount(EditTaxZonePage, {
            props: { taxZone: baseTaxZone },
        });
    });

    it('renders without errors', () => {
        expect(wrapper.exists()).toBe(true);
    });

    it('displays the Edit Tax Zone heading', () => {
        expect(wrapper.text()).toContain('Edit Tax Zone');
    });

    it('shows the zone name in the page', () => {
        expect(wrapper.text()).toContain('Ontario');
    });

    it('renders the Zone Name field label', () => {
        expect(wrapper.text()).toContain('Zone Name');
    });

    it('renders the Country Code field label', () => {
        expect(wrapper.text()).toContain('Country Code');
    });

    it('renders the Priority field label', () => {
        expect(wrapper.text()).toContain('Priority');
    });

    it('renders the Active checkbox', () => {
        expect(wrapper.text()).toContain('Active');
    });

    it('renders the existing rate name', () => {
        expect(wrapper.text()).toContain('HST');
    });

    it('renders the Add Rate section', () => {
        expect(wrapper.text()).toContain('Add Rate');
    });
});
