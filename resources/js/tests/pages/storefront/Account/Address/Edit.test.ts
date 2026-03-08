import AddressEdit from '@/pages/storefront/Account/Address/Edit.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Form: {
        name: 'Form',
        template: '<form><slot :errors="{}" :processing="false" :recentlySuccessful="false" /></form>',
        props: ['action', 'method', 'setDefaultsOnSuccess'],
    },
}));

vi.mock('@/layouts/AccountLayout.vue', () => ({
    default: { name: 'AccountLayout', template: '<div><slot /></div>', props: ['title'] },
}));

vi.mock('@/components/InputError.vue', () => ({
    default: { name: 'InputError', template: '<span />', props: ['message'] },
}));

vi.mock('@/routes/account/address', () => ({
    update: { form: vi.fn(() => ({ action: '/account/address', method: 'post' })) },
}));

const existingAddress = {
    id: 1,
    name: 'Jane Doe',
    line1: '123 Main St',
    line2: 'Unit 4B',
    city: 'Manila',
    state: 'Metro Manila',
    postal_code: '1000',
    country: 'PH',
};

describe('Account/Address/Edit', () => {
    it('renders without errors when address is null', () => {
        const wrapper = mount(AddressEdit, { props: { address: null } });
        expect(wrapper.exists()).toBe(true);
    });

    it('renders the save address button', () => {
        const wrapper = mount(AddressEdit, { props: { address: null } });
        expect(wrapper.text()).toContain('Save address');
    });

    it('pre-fills the name field from an existing address', () => {
        const wrapper = mount(AddressEdit, { props: { address: existingAddress } });
        const nameInput = wrapper.find('input[name="name"]');
        expect(nameInput.attributes('value')).toBe('Jane Doe');
    });

    it('pre-fills the address line 1 from an existing address', () => {
        const wrapper = mount(AddressEdit, { props: { address: existingAddress } });
        const line1 = wrapper.find('input[name="line1"]');
        expect(line1.attributes('value')).toBe('123 Main St');
    });

    it('pre-fills the city from an existing address', () => {
        const wrapper = mount(AddressEdit, { props: { address: existingAddress } });
        const city = wrapper.find('input[name="city"]');
        expect(city.attributes('value')).toBe('Manila');
    });

    it('pre-fills the postal code from an existing address', () => {
        const wrapper = mount(AddressEdit, { props: { address: existingAddress } });
        const postal = wrapper.find('input[name="postal_code"]');
        expect(postal.attributes('value')).toBe('1000');
    });

    it('renders all required form fields', () => {
        const wrapper = mount(AddressEdit, { props: { address: null } });
        expect(wrapper.find('input[name="name"]').exists()).toBe(true);
        expect(wrapper.find('input[name="line1"]').exists()).toBe(true);
        expect(wrapper.find('input[name="city"]').exists()).toBe(true);
        expect(wrapper.find('input[name="postal_code"]').exists()).toBe(true);
        expect(wrapper.find('select[name="country"]').exists()).toBe(true);
    });
});
