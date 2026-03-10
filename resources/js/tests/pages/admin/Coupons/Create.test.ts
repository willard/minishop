import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import CreateCouponPage from '@/pages/admin/Coupons/Create.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: {
        name: 'Link',
        template: '<a :href="href"><slot /></a>',
        props: ['href'],
    },
    Form: {
        name: 'Form',
        template: '<form><slot :errors="{}" :processing="false" /></form>',
        props: ['action', 'method'],
    },
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
        props: [
            'id',
            'name',
            'modelValue',
            'defaultValue',
            'type',
            'min',
            'placeholder',
            'class',
            'required',
        ],
    },
}));

vi.mock('@/components/ui/label', () => ({
    Label: {
        name: 'Label',
        template: '<label><slot /></label>',
        props: ['for'],
    },
}));

vi.mock('@/components/ui/checkbox', () => ({
    Checkbox: {
        name: 'Checkbox',
        template: '<input type="checkbox" />',
        props: ['id', 'name', 'value', 'defaultValue'],
    },
}));

vi.mock('@/components/InputError.vue', () => ({
    default: { name: 'InputError', template: '<span />', props: ['message'] },
}));

vi.mock('@/actions/App/Http/Controllers/Admin/CouponController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/coupons' })),
    create: vi.fn(() => ({ url: '/dashboard/coupons/create' })),
    store: {
        form: vi.fn(() => ({ action: '/dashboard/coupons', method: 'post' })),
    },
}));

describe('admin/Coupons/Create', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        wrapper = mount(CreateCouponPage);
    });

    it('renders without errors', () => {
        expect(wrapper.exists()).toBe(true);
    });

    it('displays the Add Coupon heading', () => {
        expect(wrapper.text()).toContain('Add Coupon');
    });

    it('renders the Code field label', () => {
        expect(wrapper.text()).toContain('Code');
    });

    it('renders the Type field label', () => {
        expect(wrapper.text()).toContain('Type');
    });

    it('renders the Value field label', () => {
        expect(wrapper.text()).toContain('Value');
    });

    it('renders the Expiry Date field label', () => {
        expect(wrapper.text()).toContain('Expiry Date');
    });

    it('renders the Usage Limit field label', () => {
        expect(wrapper.text()).toContain('Usage Limit');
    });

    it('renders the Active checkbox label', () => {
        expect(wrapper.text()).toContain('Active');
    });

    it('renders percentage and fixed options in the type select', () => {
        const options = wrapper.findAll('option');
        const values = options.map((o) => o.element.value);
        expect(values).toContain('percentage');
        expect(values).toContain('fixed');
    });
});
