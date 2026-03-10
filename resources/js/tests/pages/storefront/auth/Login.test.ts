import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import LoginPage from '@/pages/storefront/auth/Login.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: {
        name: 'Link',
        template: '<a href="#"><slot /></a>',
        props: ['href'],
    },
    Form: {
        name: 'Form',
        template: '<form><slot :errors="{}" :processing="false" /></form>',
        props: ['action', 'method', 'resetOnSuccess'],
    },
}));

vi.mock('@/components/InputError.vue', () => ({
    default: { name: 'InputError', template: '<span />', props: ['message'] },
}));

vi.mock('@/routes/login', () => ({
    store: { form: vi.fn(() => ({ action: '/login', method: 'post' })) },
}));

vi.mock('@/routes/register', () => ({
    store: { form: vi.fn(() => ({ action: '/register', method: 'post' })) },
}));

const baseProps = {
    status: undefined as string | undefined,
    canResetPassword: true,
    canRegister: true,
};

describe('storefront/auth/Login', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        wrapper = mount(LoginPage, { props: baseProps });
    });

    it('renders without errors', () => {
        expect(wrapper.exists()).toBe(true);
    });

    it('renders the email and password inputs', () => {
        expect(wrapper.find('input[type="email"]').exists()).toBe(true);
        expect(wrapper.find('input[type="password"]').exists()).toBe(true);
    });

    it('renders the sign in submit button', () => {
        expect(wrapper.text()).toContain('Sign in');
    });

    it('shows the forgot password link when canResetPassword is true', () => {
        expect(wrapper.text()).toContain('Forgot password?');
    });

    it('hides the forgot password link when canResetPassword is false', () => {
        const noReset = mount(LoginPage, {
            props: { ...baseProps, canResetPassword: false },
        });
        expect(noReset.text()).not.toContain('Forgot password?');
    });

    it('shows the register link when canRegister is true', () => {
        expect(wrapper.text()).toContain('Create one');
    });

    it('hides the register link when canRegister is false', () => {
        const noRegister = mount(LoginPage, {
            props: { ...baseProps, canRegister: false },
        });
        expect(noRegister.text()).not.toContain('Create one');
    });

    it('shows the status flash message when status is provided', () => {
        const withStatus = mount(LoginPage, {
            props: { ...baseProps, status: 'Password reset successfully.' },
        });
        expect(withStatus.text()).toContain('Password reset successfully.');
    });

    it('does not show a status message when status is absent', () => {
        expect(wrapper.find('[style*="e8f5e9"]').exists()).toBe(false);
    });
});
