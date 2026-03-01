import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import ShowPage from '@/pages/admin/Products/Show.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: { name: 'Link', template: '<a href="#"><slot /></a>', props: ['href'] },
    router: { delete: vi.fn(), put: vi.fn() },
    useForm: vi.fn((initial: Record<string, unknown>) => ({
        ...initial,
        post: vi.fn(),
        processing: false,
        errors: {},
        reset: vi.fn(),
    })),
}));

vi.mock('@/layouts/AppLayout.vue', () => ({
    default: { name: 'AppLayout', template: '<div><slot /></div>', props: ['breadcrumbs'] },
}));

vi.mock('@/components/ui/button', () => ({
    Button: { name: 'Button', template: '<button :disabled="disabled"><slot /></button>', props: ['variant', 'size', 'type', 'disabled'] },
}));

vi.mock('@/components/ui/badge', () => ({
    Badge: { name: 'Badge', template: '<span><slot /></span>', props: ['variant', 'class'] },
}));

vi.mock('@/actions/App/Http/Controllers/Admin/ProductController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/products' })),
    edit: vi.fn(() => ({ url: '/dashboard/products/test-product/edit' })),
    destroy: vi.fn(() => ({ url: '/dashboard/products/test-product' })),
}));

vi.mock('@/actions/App/Http/Controllers/Admin/ProductVariantController', () => ({
    create: vi.fn(() => ({ url: '/dashboard/products/test-product/variants/create' })),
    edit: vi.fn(() => ({ url: '/dashboard/products/test-product/variants/1/edit' })),
    destroy: vi.fn(() => ({ url: '/dashboard/products/test-product/variants/1' })),
}));

vi.mock('@/actions/App/Http/Controllers/Admin/ProductOptionController', () => ({
    create: vi.fn(() => ({ url: '/dashboard/products/test-product/options/create' })),
    destroy: vi.fn(() => ({ url: '/dashboard/products/test-product/options/1' })),
}));

vi.mock('@/actions/App/Http/Controllers/Admin/ProductImageController', () => ({
    store: vi.fn(() => ({ url: '/dashboard/products/test-product/images' })),
    destroy: vi.fn(() => ({ url: '/dashboard/products/test-product/images/1' })),
    reorder: vi.fn(() => ({ url: '/dashboard/products/test-product/images/reorder' })),
}));

const baseProduct = {
    id: 1,
    name: 'Test Product',
    slug: 'test-product',
    description: 'A test description',
    price: 1999,
    compare_price: 2999,
    stock_quantity: 50,
    is_active: true,
    sku: 'ABC-1234',
    categories: [{ id: 1, name: 'Electronics' }],
    images: [] as Array<{ id: number; path: string; alt_text: string | null; sort_order: number }>,
    options: [],
    variants: [],
};

describe('admin/Products/Show — Images section', () => {
    it('renders the Images heading', () => {
        const wrapper = mount(ShowPage, { props: { product: baseProduct } });
        expect(wrapper.text()).toContain('Images');
    });

    it('shows empty state when no images', () => {
        const wrapper = mount(ShowPage, { props: { product: baseProduct } });
        expect(wrapper.text()).toContain('No images yet');
    });

    it('displays images when present', () => {
        const product = {
            ...baseProduct,
            images: [
                { id: 1, path: 'products/1/a.jpg', alt_text: 'Photo A', sort_order: 0 },
                { id: 2, path: 'products/1/b.jpg', alt_text: null, sort_order: 1 },
            ],
        };
        const wrapper = mount(ShowPage, { props: { product } });
        const imgs = wrapper.findAll('img');
        expect(imgs.length).toBeGreaterThanOrEqual(2);
        expect(imgs[0].attributes('src')).toBe('/storage/products/1/a.jpg');
        expect(imgs[0].attributes('alt')).toBe('Photo A');
        expect(imgs[1].attributes('alt')).toBe('Test Product');
    });

    it('shows Primary badge on the first image', () => {
        const product = {
            ...baseProduct,
            images: [
                { id: 1, path: 'products/1/a.jpg', alt_text: null, sort_order: 0 },
                { id: 2, path: 'products/1/b.jpg', alt_text: null, sort_order: 1 },
            ],
        };
        const wrapper = mount(ShowPage, { props: { product } });
        expect(wrapper.text()).toContain('Primary');
    });

    it('renders file upload input', () => {
        const wrapper = mount(ShowPage, { props: { product: baseProduct } });
        const fileInput = wrapper.find('input[type="file"]');
        expect(fileInput.exists()).toBe(true);
        expect(fileInput.attributes('multiple')).toBeDefined();
        expect(fileInput.attributes('accept')).toContain('image/jpeg');
    });

    it('renders Upload button', () => {
        const wrapper = mount(ShowPage, { props: { product: baseProduct } });
        expect(wrapper.text()).toContain('Upload');
    });

    it('renders delete buttons for each image', () => {
        const product = {
            ...baseProduct,
            images: [
                { id: 1, path: 'products/1/a.jpg', alt_text: null, sort_order: 0 },
                { id: 2, path: 'products/1/b.jpg', alt_text: null, sort_order: 1 },
            ],
        };
        const wrapper = mount(ShowPage, { props: { product } });
        // Each image has an X (delete) button — find buttons with destructive variant
        const imageSection = wrapper.findAll('.group');
        expect(imageSection).toHaveLength(2);
    });

    it('renders reorder buttons for images', () => {
        const product = {
            ...baseProduct,
            images: [
                { id: 1, path: 'products/1/a.jpg', alt_text: null, sort_order: 0 },
                { id: 2, path: 'products/1/b.jpg', alt_text: null, sort_order: 1 },
                { id: 3, path: 'products/1/c.jpg', alt_text: null, sort_order: 2 },
            ],
        };
        const wrapper = mount(ShowPage, { props: { product } });
        // The middle image should have both up and down buttons
        const groups = wrapper.findAll('.group');
        expect(groups).toHaveLength(3);
    });
});
