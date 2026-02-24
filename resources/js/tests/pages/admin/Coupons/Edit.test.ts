import EditCouponPage from '@/pages/admin/Coupons/Edit.vue';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: { name: 'Link', template: '<a :href="href"><slot /></a>', props: ['href'] },
    Form: {
        name: 'Form',
        template: '<form><slot :errors="{}" :processing="false" /></form>',
        props: ['action', 'method'],
    },
}));

vi.mock('@/layouts/AppLayout.vue', () => ({
    default: { name: 'AppLayout', template: '<div><slot /></div>', props: ['breadcrumbs'] },
}));

vi.mock('@/components/ui/button', () => ({
    Button: { name: 'Button', template: '<button><slot /></button>', props: ['variant', 'size', 'type', 'disabled'] },
}));

vi.mock('@/components/ui/input', () => ({
    Input: {
        name: 'Input',
        template: '<input />',
        props: ['id', 'name', 'modelValue', 'defaultValue', 'type', 'min', 'placeholder', 'class', 'required'],
    },
}));

vi.mock('@/components/ui/label', () => ({
    Label: { name: 'Label', template: '<label><slot /></label>', props: ['for'] },
}));

vi.mock('@/components/ui/checkbox', () => ({
    Checkbox: { name: 'Checkbox', template: '<input type="checkbox" />', props: ['id', 'name', 'value', 'defaultValue'] },
}));

vi.mock('@/components/InputError.vue', () => ({
    default: { name: 'InputError', template: '<span />', props: ['message'] },
}));

vi.mock('@/actions/App/Http/Controllers/Admin/CouponController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/coupons' })),
    update: { form: vi.fn((c: { id: number }) => ({ action: `/dashboard/coupons/${c.id}`, method: 'put' })) },
}));

const baseCoupon = {
    id: 1,
    code: 'SAVE10',
    description: '10% off everything',
    type: 'percentage' as const,
    value: 10,
    minimum_order_amount: null,
    expiry_date: null,
    usage_limit: 50,
    used_count: 7,
    is_active: true,
};

describe('admin/Coupons/Edit', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        wrapper = mount(EditCouponPage, {
            props: { coupon: baseCoupon },
        });
    });

    it('renders without errors', () => {
        expect(wrapper.exists()).toBe(true);
    });

    it('displays the Edit Coupon heading', () => {
        expect(wrapper.text()).toContain('Edit Coupon');
    });

    it('displays the coupon code in the header', () => {
        expect(wrapper.text()).toContain('SAVE10');
    });

    it('displays Save Changes button', () => {
        expect(wrapper.text()).toContain('Save Changes');
    });

    it('shows usage stats', () => {
        expect(wrapper.text()).toContain('7');
        expect(wrapper.text()).toContain('50');
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

    it('renders the Active checkbox label', () => {
        expect(wrapper.text()).toContain('Active');
    });
});
