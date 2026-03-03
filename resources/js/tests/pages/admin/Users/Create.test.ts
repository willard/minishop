import CreateUserPage from '@/pages/admin/Users/Create.vue';
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

vi.mock('@/components/InputError.vue', () => ({
    default: { name: 'InputError', template: '<span />', props: ['message'] },
}));

vi.mock('@/actions/App/Http/Controllers/Admin/UserController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/users' })),
    create: vi.fn(() => ({ url: '/dashboard/users/create' })),
    store: { form: vi.fn(() => ({ action: '/dashboard/users', method: 'post' })) },
}));

describe('admin/Users/Create', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        wrapper = mount(CreateUserPage, {
            props: { roles: ['super-admin', 'admin', 'manager'] },
        });
    });

    it('renders without errors', () => {
        expect(wrapper.exists()).toBe(true);
    });

    it('displays the Add User heading', () => {
        expect(wrapper.text()).toContain('Add User');
    });

    it('renders the Name field label', () => {
        expect(wrapper.text()).toContain('Name');
    });

    it('renders the Email field label', () => {
        expect(wrapper.text()).toContain('Email');
    });

    it('renders the Password field label', () => {
        expect(wrapper.text()).toContain('Password');
    });

    it('renders the Confirm Password field label', () => {
        expect(wrapper.text()).toContain('Confirm Password');
    });

    it('renders the Role field label', () => {
        expect(wrapper.text()).toContain('Role');
    });

    it('renders role options from props', () => {
        const options = wrapper.findAll('option');
        const values = options.map((o) => o.element.value);
        expect(values).toContain('super-admin');
        expect(values).toContain('admin');
        expect(values).toContain('manager');
    });

    it('renders the Create User submit button', () => {
        expect(wrapper.text()).toContain('Create User');
    });
});
