import EditUserPage from '@/pages/admin/Users/Edit.vue';
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
    usePage: vi.fn(() => ({
        props: {
            auth: {
                user: { id: 99 },
                roles: ['super-admin'],
                permissions: [],
            },
        },
    })),
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
    update: { form: vi.fn((u: { id: number }) => ({ action: `/dashboard/users/${u.id}`, method: 'put' })) },
}));

const baseUser = {
    id: 5,
    name: 'Bob Admin',
    email: 'bob@example.com',
};

describe('admin/Users/Edit', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        wrapper = mount(EditUserPage, {
            props: {
                user: baseUser,
                currentRole: 'admin',
                roles: ['super-admin', 'admin', 'manager'],
            },
        });
    });

    it('renders without errors', () => {
        expect(wrapper.exists()).toBe(true);
    });

    it('displays the Edit User heading', () => {
        expect(wrapper.text()).toContain('Edit User');
    });

    it('displays the user name in the header', () => {
        expect(wrapper.text()).toContain('Bob Admin');
    });

    it('displays Save Changes button', () => {
        expect(wrapper.text()).toContain('Save Changes');
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

    it('renders the Role field label', () => {
        expect(wrapper.text()).toContain('Role');
    });

    it('renders role options', () => {
        const options = wrapper.findAll('option');
        const values = options.map((o) => o.element.value);
        expect(values).toContain('super-admin');
        expect(values).toContain('admin');
        expect(values).toContain('manager');
    });
});
