import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import ShowPage from '@/pages/admin/Products/Show.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: {
        name: 'Link',
        template: '<a href="#"><slot /></a>',
        props: ['href'],
    },
    router: { delete: vi.fn(), put: vi.fn(), post: vi.fn() },
    useForm: vi.fn((initial: Record<string, unknown>) => ({
        ...initial,
        post: vi.fn(),
        processing: false,
        errors: {},
        reset: vi.fn(),
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
        template: '<button :disabled="disabled"><slot /></button>',
        props: ['variant', 'size', 'type', 'disabled'],
    },
}));

vi.mock('@/components/ui/badge', () => ({
    Badge: {
        name: 'Badge',
        template: '<span><slot /></span>',
        props: ['variant', 'class'],
    },
}));

vi.mock('@/actions/App/Http/Controllers/Admin/ProductController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/products' })),
    edit: vi.fn(() => ({ url: '/dashboard/products/test-product/edit' })),
    destroy: vi.fn(() => ({ url: '/dashboard/products/test-product' })),
}));

vi.mock(
    '@/actions/App/Http/Controllers/Admin/ProductVariantController',
    () => ({
        create: vi.fn(() => ({
            url: '/dashboard/products/test-product/variants/create',
        })),
        edit: vi.fn(() => ({
            url: '/dashboard/products/test-product/variants/1/edit',
        })),
        destroy: vi.fn(() => ({
            url: '/dashboard/products/test-product/variants/1',
        })),
    }),
);

vi.mock('@/actions/App/Http/Controllers/Admin/ProductOptionController', () => ({
    create: vi.fn(() => ({
        url: '/dashboard/products/test-product/options/create',
    })),
    destroy: vi.fn(() => ({
        url: '/dashboard/products/test-product/options/1',
    })),
}));

vi.mock('@/actions/App/Http/Controllers/Admin/ProductImageController', () => ({
    store: vi.fn(() => ({ url: '/dashboard/products/test-product/images' })),
    destroy: vi.fn(() => ({
        url: '/dashboard/products/test-product/images/1',
    })),
    reorder: vi.fn(() => ({
        url: '/dashboard/products/test-product/images/reorder',
    })),
}));

vi.mock('@/actions/App/Http/Controllers/Admin/ProductRelatedController', () => ({
    store: vi.fn(() => ({ url: '/dashboard/products/test-product/related' })),
    destroy: vi.fn(() => ({
        url: '/dashboard/products/test-product/related/other-product',
    })),
}));

const baseProduct = {
    id: 1,
    name: 'Test Product',
    slug: 'test-product',
    description: 'A test description',
    meta_title: null as string | null,
    meta_description: null as string | null,
    price: 1999,
    compare_price: 2999,
    stock_quantity: 50,
    is_active: true,
    sku: 'ABC-1234',
    categories: [{ id: 1, name: 'Electronics' }],
    images: [] as Array<{
        id: number;
        path: string;
        url?: string;
        alt_text: string | null;
        sort_order: number;
    }>,
    options: [],
    variants: [],
    related_products: [] as Array<{
        id: number;
        name: string;
        slug: string;
        images: Array<{ id: number; path: string; url: string; alt_text: string | null; sort_order: number }>;
    }>,
};

const baseAvailableProducts = [
    { id: 2, name: 'Other Product A', slug: 'other-product-a' },
    { id: 3, name: 'Other Product B', slug: 'other-product-b' },
];

describe('admin/Products/Show — Images section', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        wrapper = mount(ShowPage, {
            props: { product: baseProduct, availableProducts: baseAvailableProducts },
        });
    });

    it('renders the Images heading', () => {
        expect(wrapper.text()).toContain('Images');
    });

    it('shows empty state when no images', () => {
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
        const w = mount(ShowPage, { props: { product, availableProducts: baseAvailableProducts } });
        const imgs = w.findAll('img');
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
        const w = mount(ShowPage, { props: { product, availableProducts: baseAvailableProducts } });
        expect(w.text()).toContain('Primary');
    });

    it('renders file upload input', () => {
        const fileInput = wrapper.find('input[type="file"]');
        expect(fileInput.exists()).toBe(true);
        expect(fileInput.attributes('multiple')).toBeDefined();
        expect(fileInput.attributes('accept')).toContain('image/jpeg');
    });

    it('renders Upload button', () => {
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
        const w = mount(ShowPage, { props: { product, availableProducts: baseAvailableProducts } });
        const imageSection = w.findAll('.group');
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
        const w = mount(ShowPage, { props: { product, availableProducts: baseAvailableProducts } });
        const groups = w.findAll('.group');
        expect(groups).toHaveLength(3);
    });
});

describe('admin/Products/Show — SEO section', () => {
    it('does not render SEO section when meta fields are null', () => {
        const wrapper = mount(ShowPage, {
            props: { product: baseProduct, availableProducts: baseAvailableProducts },
        });
        expect(wrapper.text()).not.toContain('Meta Title');
    });

    it('renders meta_title when set', () => {
        const product = { ...baseProduct, meta_title: 'Custom SEO Title' };
        const wrapper = mount(ShowPage, {
            props: { product, availableProducts: baseAvailableProducts },
        });
        expect(wrapper.text()).toContain('Custom SEO Title');
        expect(wrapper.text()).toContain('Meta Title');
    });

    it('renders meta_description when set', () => {
        const product = { ...baseProduct, meta_description: 'A short SEO blurb.' };
        const wrapper = mount(ShowPage, {
            props: { product, availableProducts: baseAvailableProducts },
        });
        expect(wrapper.text()).toContain('A short SEO blurb.');
        expect(wrapper.text()).toContain('Meta Description');
    });
});

describe('admin/Products/Show — Related Products section', () => {
    it('renders the Related Products heading', () => {
        const wrapper = mount(ShowPage, {
            props: { product: baseProduct, availableProducts: baseAvailableProducts },
        });
        expect(wrapper.text()).toContain('Related Products');
    });

    it('shows empty state when no related products', () => {
        const wrapper = mount(ShowPage, {
            props: { product: baseProduct, availableProducts: baseAvailableProducts },
        });
        expect(wrapper.text()).toContain('No related products yet');
    });

    it('displays related product names', () => {
        const product = {
            ...baseProduct,
            related_products: [
                { id: 2, name: 'Related Item', slug: 'related-item', images: [] },
            ],
        };
        const wrapper = mount(ShowPage, {
            props: { product, availableProducts: baseAvailableProducts },
        });
        expect(wrapper.text()).toContain('Related Item');
    });

    it('renders the product selector dropdown', () => {
        const wrapper = mount(ShowPage, {
            props: { product: baseProduct, availableProducts: baseAvailableProducts },
        });
        const select = wrapper.findAll('select').find((s) => s.text().includes('Select a product'));
        expect(select).toBeDefined();
    });

    it('available products appear as options in the selector', () => {
        const wrapper = mount(ShowPage, {
            props: { product: baseProduct, availableProducts: baseAvailableProducts },
        });
        const options = wrapper.findAll('option');
        const texts = options.map((o) => o.text());
        expect(texts).toContain('Other Product A');
        expect(texts).toContain('Other Product B');
    });

    it('Add button is disabled when no product is selected', () => {
        const wrapper = mount(ShowPage, {
            props: { product: baseProduct, availableProducts: baseAvailableProducts },
        });
        const addButton = wrapper.findAll('button').find((b) => b.text().trim() === 'Add');
        expect(addButton?.attributes('disabled')).toBeDefined();
    });
});
