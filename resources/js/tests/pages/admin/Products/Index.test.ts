import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import IndexPage from '@/pages/admin/Products/Index.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: {
        name: 'Link',
        template: '<a :href="href"><slot /></a>',
        props: ['href'],
    },
    router: { delete: vi.fn(), get: vi.fn() },
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
        props: ['variant', 'size'],
    },
}));

vi.mock('@/components/ui/badge', () => ({
    Badge: {
        name: 'Badge',
        template: '<span class="badge"><slot /></span>',
        props: ['variant'],
    },
}));

vi.mock('@/components/ui/input', () => ({
    Input: {
        name: 'Input',
        template: '<input v-bind="$attrs" />',
        props: ['modelValue'],
        inheritAttrs: false,
    },
}));

vi.mock('@/components/ProductTypeBadge.vue', () => ({
    default: {
        name: 'ProductTypeBadge',
        template: '<span />',
        props: ['type'],
    },
}));

vi.mock('@/components/TagBadge.vue', () => ({
    default: {
        name: 'TagBadge',
        template: '<span />',
        props: ['name', 'color'],
    },
}));

vi.mock(
    '@/actions/App/Http/Controllers/Admin/ProductBulkActionController',
    () => ({
        default: vi.fn(() => ({ url: '/dashboard/products/bulk', method: 'post' })),
    }),
);

vi.mock('@/actions/App/Http/Controllers/Admin/ProductController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/products' })),
    create: vi.fn(() => ({ url: '/dashboard/products/create' })),
    show: vi.fn((product: { slug: string }) => ({
        url: `/dashboard/products/${product.slug}`,
    })),
    edit: vi.fn((product: { slug: string }) => ({
        url: `/dashboard/products/${product.slug}/edit`,
    })),
    destroy: vi.fn((product: { slug: string }) => ({
        url: `/dashboard/products/${product.slug}`,
    })),
    exportMethod: Object.assign(
        vi.fn(() => ({ url: '/dashboard/products/export' })),
        {
            url: vi.fn((options?: { query?: Record<string, string | undefined> }) => {
                const params = new URLSearchParams();
                for (const [k, v] of Object.entries(options?.query ?? {})) {
                    if (v !== undefined) params.set(k, v);
                }
                const qs = params.toString();
                return `/dashboard/products/export${qs ? '?' + qs : ''}`;
            }),
        },
    ),
}));

const baseProducts = {
    data: [
        {
            id: 1,
            name: 'Test Shirt',
            slug: 'test-shirt',
            price: 2999,
            stock_quantity: 10,
            is_active: true,
            sku: 'SHIRT-001',
            categories: [{ id: 1, name: 'Apparel' }],
            tags: [],
        },
        {
            id: 2,
            name: 'Blue Jeans',
            slug: 'blue-jeans',
            price: 5999,
            stock_quantity: 0,
            is_active: false,
            sku: null,
            categories: [],
            tags: [],
        },
    ],
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 2,
    links: [],
};

const baseFilters: {
    search?: string;
    category_id?: string;
    stock?: string;
    sort_by?: string;
    sort_dir?: string;
    price_min?: string;
    price_max?: string;
} = {
    search: '',
    category_id: undefined,
    stock: undefined,
    sort_by: undefined,
    sort_dir: undefined,
    price_min: undefined,
    price_max: undefined,
};

const baseCategories = [
    { id: 1, name: 'Apparel' },
    { id: 2, name: 'Electronics' },
];

describe('admin/Products/Index', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        wrapper = mount(IndexPage, {
            props: {
                products: baseProducts,
                filters: baseFilters,
                categories: baseCategories,
                tags: [],
            },
        });
    });

    it('renders without errors', () => {
        expect(wrapper.exists()).toBe(true);
    });

    it('displays the total products count', () => {
        expect(wrapper.text()).toContain('2 total products');
    });

    it('displays product names', () => {
        expect(wrapper.text()).toContain('Test Shirt');
        expect(wrapper.text()).toContain('Blue Jeans');
    });

    it('renders a view link for each product pointing to the show page', () => {
        const links = wrapper.findAll('a');
        const hrefs = links.map((l) => l.attributes('href'));
        expect(hrefs).toContain('/dashboard/products/test-shirt');
        expect(hrefs).toContain('/dashboard/products/blue-jeans');
    });

    it('renders an edit link for each product', () => {
        const links = wrapper.findAll('a');
        const hrefs = links.map((l) => l.attributes('href'));
        expect(hrefs).toContain('/dashboard/products/test-shirt/edit');
        expect(hrefs).toContain('/dashboard/products/blue-jeans/edit');
    });

    it('renders the search input', () => {
        expect(wrapper.find('input').exists()).toBe(true);
    });

    it('renders stock filter buttons', () => {
        const buttons = wrapper.findAll('button');
        const buttonTexts = buttons.map((b) => b.text().trim().toLowerCase());
        expect(buttonTexts).toContain('all stock');
        expect(buttonTexts).toContain('in stock');
        expect(buttonTexts).toContain('out of stock');
    });

    it('renders category filter as a select dropdown', () => {
        const select = wrapper.find('select');
        expect(select.exists()).toBe(true);
        const options = select.findAll('option');
        const optionTexts = options.map((o) => o.text().trim());
        expect(optionTexts).toContain('All Categories');
        expect(optionTexts).toContain('Apparel');
        expect(optionTexts).toContain('Electronics');
    });

    it('renders sortable column headers for name, sku, price, stock, and status', () => {
        const buttons = wrapper.findAll('button');
        const buttonTexts = buttons.map((b) => b.text().trim().toLowerCase());
        expect(buttonTexts.some((t) => t.includes('name'))).toBe(true);
        expect(buttonTexts.some((t) => t.includes('sku'))).toBe(true);
        expect(buttonTexts.some((t) => t.includes('price'))).toBe(true);
        expect(buttonTexts.some((t) => t.includes('stock'))).toBe(true);
        expect(buttonTexts.some((t) => t.includes('status'))).toBe(true);
    });

    it('clicking a sort header calls router.get with sort_by and sort_dir', async () => {
        const { router } = await import('@inertiajs/vue3');
        const sortButtons = wrapper.findAll('thead button');
        await sortButtons[0].trigger('click');
        expect(router.get).toHaveBeenCalledWith(
            '/dashboard/products',
            expect.objectContaining({ sort_by: expect.any(String), sort_dir: 'asc' }),
            expect.any(Object),
        );
    });

    it('renders CSV and PDF export links', () => {
        const links = wrapper.findAll('a');
        const hrefs = links.map((l) => l.attributes('href') ?? '');
        expect(hrefs.some((h) => h.includes('format=csv'))).toBe(true);
        expect(hrefs.some((h) => h.includes('format=pdf'))).toBe(true);
    });

    it('export links include active filter params', () => {
        const filteredWrapper = mount(IndexPage, {
            props: {
                products: baseProducts,
                filters: { search: 'shirt', stock: 'in_stock', sort_by: 'price', sort_dir: 'asc' },
                categories: baseCategories,
                tags: [],
            },
        });
        const links = filteredWrapper.findAll('a');
        const hrefs = links.map((l) => l.attributes('href') ?? '');
        const csvHref = hrefs.find((h) => h.includes('format=csv')) ?? '';
        expect(csvHref).toContain('search=shirt');
        expect(csvHref).toContain('stock=in_stock');
        expect(csvHref).toContain('sort_by=price');
        expect(csvHref).toContain('sort_dir=asc');
    });

    it('shows empty state when no products', () => {
        const emptyWrapper = mount(IndexPage, {
            props: {
                products: { ...baseProducts, data: [], total: 0 },
                filters: baseFilters,
                categories: baseCategories,
                tags: [],
            },
        });
        expect(emptyWrapper.text()).toContain('No products yet');
    });

    it('shows "No products found." when empty and a filter is active', () => {
        const filteredWrapper = mount(IndexPage, {
            props: {
                products: { ...baseProducts, data: [], total: 0 },
                filters: {
                    search: 'missing',
                    category_id: undefined,
                    stock: undefined,
                },
                categories: baseCategories,
                tags: [],
            },
        });
        expect(filteredWrapper.text()).toContain('No products found.');
    });

    it('shows out-of-stock styling for zero stock', () => {
        expect(wrapper.html()).toContain('text-destructive');
    });

    it('renders price min and max inputs in the filter row', () => {
        const inputs = wrapper.findAll('input');
        const types = inputs.map((i) => i.attributes('type'));
        const placeholders = inputs.map((i) => i.attributes('placeholder') ?? '');
        expect(types.filter((t) => t === 'number').length).toBeGreaterThanOrEqual(2);
        expect(placeholders).toContain('Min');
        expect(placeholders).toContain('Max');
    });

    it('shows "No products found." when empty and price_min filter is active', () => {
        const filteredWrapper = mount(IndexPage, {
            props: {
                products: { ...baseProducts, data: [], total: 0 },
                filters: { price_min: '10' },
                categories: baseCategories,
                tags: [],
            },
        });
        expect(filteredWrapper.text()).toContain('No products found.');
    });

    it('export links include price_min and price_max when set', () => {
        const filteredWrapper = mount(IndexPage, {
            props: {
                products: baseProducts,
                filters: { price_min: '10', price_max: '50', format: undefined },
                categories: baseCategories,
                tags: [],
            },
        });
        const links = filteredWrapper.findAll('a');
        const hrefs = links.map((l) => l.attributes('href') ?? '');
        const csvHref = hrefs.find((h) => h.includes('format=csv')) ?? '';
        expect(csvHref).toContain('price_min=10');
        expect(csvHref).toContain('price_max=50');
    });
});
