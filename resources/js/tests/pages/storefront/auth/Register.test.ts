import RegisterPage from '@/pages/storefront/auth/Register.vue';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: { name: 'Link', template: '<a href="#"><slot /></a>', props: ['href'] },
    Form: { name: 'Form', template: '<form><slot :errors="{}" :processing="false" /></form>', props: ['action', 'method', 'resetOnSuccess'] },
}));

vi.mock('@/components/InputError.vue', () => ({
    default: { name: 'InputError', template: '<span />', props: ['message'] },
}));

vi.mock('@/routes/register', () => ({
    store: { form: vi.fn(() => ({ action: '/register', method: 'post' })) },
}));

describe('storefront/auth/Register', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        wrapper = mount(RegisterPage);
    });

    it('renders without errors', () => {
        expect(wrapper.exists()).toBe(true);
    });

    it('renders the name input', () => {
        expect(wrapper.find('input[name="name"]').exists()).toBe(true);
    });

    it('renders the email input', () => {
        expect(wrapper.find('input[type="email"]').exists()).toBe(true);
    });

    it('renders the password input', () => {
        expect(wrapper.find('input[name="password"]').exists()).toBe(true);
    });

    it('renders the password confirmation input', () => {
        expect(wrapper.find('input[name="password_confirmation"]').exists()).toBe(true);
    });

    it('renders the create account submit button', () => {
        expect(wrapper.text()).toContain('Create account');
    });

    it('renders a link back to the login page', () => {
        expect(wrapper.text()).toContain('Sign in');
    });

    it('renders the Minishop brand', () => {
        expect(wrapper.text()).toContain('Minishop');
    });
});
