import PaymentIndex from '@/pages/storefront/Account/Payment/Index.vue';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
}));

vi.mock('@/layouts/AccountLayout.vue', () => ({
    default: { name: 'AccountLayout', template: '<div><slot /></div>', props: ['title'] },
}));

describe('Account/Payment/Index', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        wrapper = mount(PaymentIndex);
    });

    it('renders without errors', () => {
        expect(wrapper.exists()).toBe(true);
    });

    it('shows a message indicating no saved payment methods', () => {
        expect(wrapper.text()).toContain('No saved payment methods');
    });

    it('shows a message about Stripe configuration', () => {
        expect(wrapper.text()).toContain('Stripe');
    });
});
