import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import MediaIndexPage from '@/pages/admin/Media/Index.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    router: { get: vi.fn(), delete: vi.fn() },
    useForm: () => ({
        file: null,
        alt_text: '',
        processing: false,
        errors: {},
        post: vi.fn(),
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

vi.mock('@/actions/App/Http/Controllers/Admin/MediaController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/media' })),
    store: vi.fn(() => ({ url: '/dashboard/media' })),
    update: vi.fn((id: number) => ({ url: `/dashboard/media/${id}` })),
    destroy: vi.fn((id: number) => ({ url: `/dashboard/media/${id}` })),
}));

const basePagination = {
    data: [
        {
            id: 1,
            disk: 'public',
            path: 'media/2026/04/logo.png',
            url: '/storage/media/2026/04/logo.png',
            original_name: 'logo.png',
            mime_type: 'image/png',
            size: 12345,
            alt_text: 'Logo',
            created_at: '2026-04-15T00:00:00Z',
            uploader: { id: 1, name: 'Admin' },
        },
    ],
    current_page: 1,
    last_page: 1,
    total: 1,
    links: [],
};

describe('admin/Media/Index', () => {
    it('renders heading', () => {
        const wrapper = mount(MediaIndexPage, {
            props: { media: basePagination, filters: {} },
        });
        expect(wrapper.text()).toContain('Media');
    });

    it('renders uploaded media', () => {
        const wrapper = mount(MediaIndexPage, {
            props: { media: basePagination, filters: {} },
        });
        expect(wrapper.text()).toContain('logo.png');
    });

    it('shows empty state when no media', () => {
        const wrapper = mount(MediaIndexPage, {
            props: {
                media: { ...basePagination, data: [], total: 0 },
                filters: {},
            },
        });
        expect(wrapper.text().toLowerCase()).toContain('no media');
    });
});
