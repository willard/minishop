import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import UsersIndexPage from '@/pages/admin/Users/Index.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: {
        name: 'Link',
        template: '<a :href="href"><slot /></a>',
        props: ['href'],
    },
    router: { delete: vi.fn() },
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
    default: {
        name: 'AppLayout',
        template: '<div><slot /></div>',
        props: ['breadcrumbs'],
    },
}));

vi.mock('@/components/ui/button', () => ({
    Button: {
        name: 'Button',
        template: '<button @click="$emit(\'click\')"><slot /></button>',
        props: ['variant', 'size', 'type'],
    },
}));

vi.mock('@/components/ui/badge', () => ({
    Badge: {
        name: 'Badge',
        template: '<span><slot /></span>',
        props: ['variant'],
    },
}));

vi.mock('@/composables/useCan', () => ({
    useCan: () => ({
        can: () => true,
        hasRole: () => true,
    }),
}));

vi.mock('@/actions/App/Http/Controllers/Admin/UserController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/users' })),
    create: vi.fn(() => ({ url: '/dashboard/users/create' })),
    edit: vi.fn((u: { id: number }) => ({
        url: `/dashboard/users/${u.id}/edit`,
    })),
    destroy: vi.fn((u: { id: number }) => ({
        url: `/dashboard/users/${u.id}`,
    })),
}));

const basePagination = {
    data: [
        {
            id: 1,
            name: 'Alice Admin',
            email: 'alice@example.com',
            email_verified_at: '2026-01-01T00:00:00.000000Z',
            created_at: '2026-01-01T00:00:00.000000Z',
            roles: [{ id: 1, name: 'super-admin' }],
        },
        {
            id: 2,
            name: 'Bob Manager',
            email: 'bob@example.com',
            email_verified_at: '2026-02-01T00:00:00.000000Z',
            created_at: '2026-02-01T00:00:00.000000Z',
            roles: [{ id: 2, name: 'admin' }],
        },
    ],
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 2,
    links: [],
};

describe('admin/Users/Index', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        wrapper = mount(UsersIndexPage, {
            props: { users: basePagination },
        });
    });

    it('renders without errors', () => {
        expect(wrapper.exists()).toBe(true);
    });

    it('displays the page title', () => {
        expect(wrapper.text()).toContain('Users');
    });

    it('displays the total user count', () => {
        expect(wrapper.text()).toContain('2 staff users');
    });

    it('renders a row for each user', () => {
        const rows = wrapper.findAll('tbody tr');
        expect(rows).toHaveLength(2);
    });

    it('displays user names', () => {
        expect(wrapper.text()).toContain('Alice Admin');
        expect(wrapper.text()).toContain('Bob Manager');
    });

    it('displays user emails', () => {
        expect(wrapper.text()).toContain('alice@example.com');
        expect(wrapper.text()).toContain('bob@example.com');
    });

    it('displays role badges', () => {
        expect(wrapper.text()).toContain('super-admin');
        expect(wrapper.text()).toContain('admin');
    });

    it('shows an empty state when there are no users', () => {
        const emptyWrapper = mount(UsersIndexPage, {
            props: {
                users: { ...basePagination, data: [], total: 0 },
            },
        });
        expect(emptyWrapper.text()).toContain('No users yet');
    });

    it('shows the Add User button', () => {
        expect(wrapper.text()).toContain('Add User');
    });
});
