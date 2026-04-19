import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import MenusIndexPage from '@/pages/admin/Menus/Index.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    router: { get: vi.fn(), post: vi.fn(), delete: vi.fn() },
    useForm: () => ({
        menu_location: '',
        label: '',
        url: '',
        target: '_self',
        sort_order: 0,
        processing: false,
        errors: {},
        post: vi.fn(),
        put: vi.fn(),
        reset: vi.fn(),
    }),
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
        template: '<button><slot /></button>',
        props: ['variant', 'size', 'type', 'disabled'],
    },
}));

vi.mock('@/components/ui/input', () => ({
    Input: {
        name: 'Input',
        template: '<input />',
        props: ['modelValue', 'placeholder', 'type'],
    },
}));

vi.mock('@/components/ui/label', () => ({
    Label: { name: 'Label', template: '<label><slot /></label>' },
}));

vi.mock('@/components/InputError.vue', () => ({
    default: { name: 'InputError', template: '<div />', props: ['message'] },
}));

vi.mock('@/actions/App/Http/Controllers/Admin/MenuController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/menus' })),
    store: vi.fn(() => ({ url: '/dashboard/menus' })),
    update: vi.fn((id: number) => ({ url: `/dashboard/menus/${id}` })),
    destroy: vi.fn((id: number) => ({ url: `/dashboard/menus/${id}` })),
    reorder: vi.fn(() => ({ url: '/dashboard/menus/reorder' })),
}));

const menus = {
    header_primary: {
        label: 'Header',
        items: [
            {
                id: 1,
                menu_location: 'header_primary',
                label: 'About',
                url: '/pages/about',
                target: '_self',
                sort_order: 1,
                parent_id: null,
            },
        ],
    },
    footer_legal: { label: 'Footer Legal', items: [] },
};

const locations = [
    { value: 'header_primary', label: 'Header' },
    { value: 'footer_legal', label: 'Footer Legal' },
];

describe('admin/Menus/Index', () => {
    it('renders heading', () => {
        const wrapper = mount(MenusIndexPage, {
            props: { menus, locations },
        });
        expect(wrapper.text()).toContain('Navigation Menus');
    });

    it('lists items for the active tab', () => {
        const wrapper = mount(MenusIndexPage, {
            props: { menus, locations },
        });
        expect(wrapper.text()).toContain('About');
        expect(wrapper.text()).toContain('/pages/about');
    });

    it('shows empty state when tab has no items', async () => {
        const wrapper = mount(MenusIndexPage, {
            props: { menus, locations },
        });
        const tabs = wrapper.findAll('button');
        const footerTab = tabs.find((b) => b.text() === 'Footer Legal');
        await footerTab?.trigger('click');
        expect(wrapper.text()).toContain('No items');
    });
});
