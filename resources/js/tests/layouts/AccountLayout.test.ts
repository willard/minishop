import AccountLayout from '@/layouts/AccountLayout.vue';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: { name: 'Link', template: '<a href="#"><slot /></a>', props: ['href', 'as'] },
    usePage: () => ({
        props: { auth: { user: { name: 'Jane Doe', email: 'jane@example.com' } } },
        url: '/account',
    }),
}));

vi.mock('@/routes', () => ({
    logout: { form: vi.fn(() => ({ action: '/logout', method: 'post' })) },
}));

vi.mock('@/routes/account', () => ({
    dashboard: vi.fn(() => ({ url: '/account' })),
}));

vi.mock('@/routes/account/orders', () => ({
    index: vi.fn(() => ({ url: '/account/orders' })),
}));

vi.mock('@/routes/account/address', () => ({
    edit: vi.fn(() => ({ url: '/account/address' })),
}));

vi.mock('@/routes/account/payment', () => ({
    index: vi.fn(() => ({ url: '/account/payment' })),
}));

const mountLayout = (props: Record<string, unknown> = {}, currentUrl = '/account') =>
    mount(AccountLayout, {
        props: { title: 'My Account', ...props },
        slots: { default: '<p>Slot content here</p>' },
        global: {
            mocks: { $page: { url: currentUrl } },
        },
    });

describe('AccountLayout', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        wrapper = mountLayout();
    });

    it('renders without errors', () => {
        expect(wrapper.exists()).toBe(true);
    });

    it('renders the title prop', () => {
        expect(wrapper.text()).toContain('My Account');
    });

    it('renders the Minishop brand link', () => {
        expect(wrapper.text()).toContain('Minishop');
    });

    it('renders the signed-in user name', () => {
        expect(wrapper.text()).toContain('Jane Doe');
    });

    it('renders all navigation links', () => {
        expect(wrapper.text()).toContain('Overview');
        expect(wrapper.text()).toContain('Orders');
        expect(wrapper.text()).toContain('Billing Address');
        expect(wrapper.text()).toContain('Payment Methods');
    });

    it('renders the sign out button', () => {
        expect(wrapper.text()).toContain('Sign out');
    });

    it('renders slot content', () => {
        expect(wrapper.text()).toContain('Slot content here');
    });

    it('does not render a title heading when title prop is absent', () => {
        const noTitle = mountLayout({ title: undefined });
        expect(noTitle.find('h1').exists()).toBe(false);
    });
});
