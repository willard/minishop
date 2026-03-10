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
        template: '<input />',
        props: ['modelValue', 'placeholder', 'class'],
    },
}));

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
        },
    ],
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 2,
    links: [],
};

const baseFilters: { search?: string; category_id?: string; stock?: string } = {
    search: '',
    category_id: undefined,
    stock: undefined,
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

    it('renders category filter buttons', () => {
        const buttons = wrapper.findAll('button');
        const buttonTexts = buttons.map((b) => b.text().trim().toLowerCase());
        expect(buttonTexts).toContain('all categories');
        baseCategories.forEach((cat) => {
            expect(buttonTexts).toContain(cat.name.toLowerCase());
        });
    });

    it('shows empty state when no products', () => {
        const emptyWrapper = mount(IndexPage, {
            props: {
                products: { ...baseProducts, data: [], total: 0 },
                filters: baseFilters,
                categories: baseCategories,
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
            },
        });
        expect(filteredWrapper.text()).toContain('No products found.');
    });

    it('shows out-of-stock styling for zero stock', () => {
        expect(wrapper.html()).toContain('text-destructive');
    });
});
