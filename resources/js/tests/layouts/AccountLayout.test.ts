import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import AccountLayout from '@/layouts/AccountLayout.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: {
        name: 'Link',
        template: '<a href="#"><slot /></a>',
        props: ['href', 'as'],
    },
    usePage: () => ({
        props: {
            auth: { user: { name: 'Jane Doe', email: 'jane@example.com' } },
        },
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

const mountLayout = (
    props: Record<string, unknown> = {},
    currentUrl = '/account',
) =>
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

    it('highlights Overview as active only on the exact /account URL', () => {
        const onOverview = mountLayout({}, '/account');
        const links = onOverview.findAll('nav a');
        const overviewLink = links.find((l) => l.text() === 'Overview');
        // Vue serializes hex to rgb; check for the active background-color
        expect(overviewLink?.attributes('style')).toContain(
            'rgba(28, 26, 23, 0.06)',
        );
    });

    it('does not highlight Overview as active on /account/orders', () => {
        const onOrders = mountLayout({}, '/account/orders');
        const links = onOrders.findAll('nav a');
        const overviewLink = links.find((l) => l.text() === 'Overview');
        expect(overviewLink?.attributes('style')).not.toContain(
            'background-color: rgba(28, 26, 23, 0.06)',
        );
    });

    it('highlights Orders as active on /account/orders', () => {
        const onOrders = mountLayout({}, '/account/orders');
        const links = onOrders.findAll('nav a');
        const ordersLink = links.find((l) => l.text() === 'Orders');
        expect(ordersLink?.attributes('style')).toContain(
            'background-color: rgba(28, 26, 23, 0.06)',
        );
    });
});
