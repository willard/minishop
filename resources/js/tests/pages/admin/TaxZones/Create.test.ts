import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import CreateTaxZonePage from '@/pages/admin/TaxZones/Create.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: {
        name: 'Link',
        template: '<a :href="href"><slot /></a>',
        props: ['href'],
    },
    useForm: vi.fn(() => ({
        name: '',
        country_code: 'CA',
        province_code: '',
        is_active: true,
        priority: 0,
        errors: {},
        processing: false,
        post: vi.fn(),
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
    store: vi.fn(() => ({ url: '/dashboard/tax-zones' })),
}));

describe('admin/TaxZones/Create', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        wrapper = mount(CreateTaxZonePage);
    });

    it('renders without errors', () => {
        expect(wrapper.exists()).toBe(true);
    });

    it('displays the New Tax Zone heading', () => {
        expect(wrapper.text()).toContain('New Tax Zone');
    });

    it('renders the Zone Name field label', () => {
        expect(wrapper.text()).toContain('Zone Name');
    });

    it('renders the Country Code field label', () => {
        expect(wrapper.text()).toContain('Country Code');
    });

    it('renders the Province / State Code field label', () => {
        expect(wrapper.text()).toContain('Province / State Code');
    });

    it('renders the Priority field label', () => {
        expect(wrapper.text()).toContain('Priority');
    });

    it('renders the Active checkbox label', () => {
        expect(wrapper.text()).toContain('Active');
    });

    it('renders the Create Zone submit button', () => {
        expect(wrapper.text()).toContain('Create Zone');
    });

    it('renders province options in the select', () => {
        const options = wrapper.findAll('option');
        const codes = options.map((o) => o.element.value);
        expect(codes).toContain('ON');
        expect(codes).toContain('QC');
        expect(codes).toContain('BC');
    });
});
