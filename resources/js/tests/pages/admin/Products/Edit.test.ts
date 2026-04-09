import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import EditPage from '@/pages/admin/Products/Edit.vue';

vi.mock('@inertiajs/vue3', () => ({
    Form: {
        name: 'Form',
        template: '<form><slot :errors="{}" :processing="false" /></form>',
        props: ['action', 'method'],
    },
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: {
        name: 'Link',
        template: '<a href="#"><slot /></a>',
        props: ['href'],
    },
}));

vi.mock('@/layouts/AppLayout.vue', () => ({
    default: {
        name: 'AppLayout',
        template: '<div><slot /></div>',
        props: ['breadcrumbs'],
    },
}));

vi.mock('@/components/ui/checkbox', () => ({
    Checkbox: {
        name: 'Checkbox',
        // Maps defaultValue → checked so we can assert .checked in the DOM.
        // This means tests will FAIL if the code regresses to :default-checked.
        template:
            '<input type="checkbox" :id="id" :name="name" :value="value" :checked="defaultValue" />',
        props: ['id', 'name', 'value', 'defaultValue'],
    },
}));

vi.mock('@/components/ui/button', () => ({
    Button: {
        name: 'Button',
        template: '<button><slot /></button>',
        props: ['variant', 'size', 'type', 'disabled'],
    },
}));

vi.mock('@/components/ui/label', () => ({
    Label: {
        name: 'Label',
        template: '<label><slot /></label>',
        props: ['for'],
    },
}));

vi.mock('@/actions/App/Http/Controllers/Admin/ProductController', () => {
    const update = vi.fn(() => ({
        url: '/dashboard/products/test-product',
        method: 'put',
    }));
    update.form = vi.fn(() => ({
        action: '/dashboard/products/test-product?_method=PUT',
        method: 'post',
    }));
    return {
        index: vi.fn(() => ({ url: '/dashboard/products' })),
        show: vi.fn(() => ({ url: '/dashboard/products/test-product' })),
        update,
    };
});

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
    categories: [{ id: 2, name: 'Electronics' }],
    tags: [],
};

const availableCategories = [
    { id: 1, name: 'Clothing' },
    { id: 2, name: 'Electronics' },
    { id: 3, name: 'Books' },
];

describe('admin/Products/Edit', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        wrapper = mount(EditPage, {
            props: { product: baseProduct, categories: availableCategories, tags: [] },
        });
    });

    it('renders without errors', () => {
        expect(wrapper.exists()).toBe(true);
    });

    it('pre-fills name input with product name', () => {
        const input = wrapper.find('input[name="name"]');
        expect(input.element.value).toBe('Test Product');
    });

    it('pre-fills description textarea with product description', () => {
        const textarea = wrapper.find('textarea[name="description"]');
        expect(textarea.element.value).toBe('A test description');
    });

    it('pre-fills price input with product price', () => {
        const input = wrapper.find('input[name="price"]');
        expect(input.element.value).toBe('1999');
    });

    it('pre-fills compare price input when set', () => {
        const input = wrapper.find('input[name="compare_price"]');
        expect(input.element.value).toBe('2999');
    });

    it('pre-fills sku input with product sku', () => {
        const input = wrapper.find('input[name="sku"]');
        expect(input.element.value).toBe('ABC-1234');
    });

    it('pre-fills stock quantity input', () => {
        const input = wrapper.find('input[name="stock_quantity"]');
        expect(input.element.value).toBe('50');
    });

    it('pre-checks is_active checkbox when product is active', () => {
        const checkbox = wrapper.find('input[name="is_active"]') as {
            element: HTMLInputElement;
        };
        expect(checkbox.element.checked).toBe(true);
    });

    it('leaves is_active unchecked when product is inactive', async () => {
        const inactiveWrapper = mount(EditPage, {
            props: {
                product: { ...baseProduct, is_active: false },
                categories: availableCategories,
                tags: [],
            },
        });
        const checkbox = inactiveWrapper.find('input[name="is_active"]') as {
            element: HTMLInputElement;
        };
        expect(checkbox.element.checked).toBe(false);
    });

    it('pre-checks only categories the product belongs to', () => {
        const checkboxes = wrapper.findAll('input[name="category_ids[]"]') as {
            element: HTMLInputElement;
        }[];
        expect(checkboxes).toHaveLength(3);

        // Clothing (id:1) — not in product.categories
        expect(checkboxes[0].element.checked).toBe(false);
        // Electronics (id:2) — in product.categories
        expect(checkboxes[1].element.checked).toBe(true);
        // Books (id:3) — not in product.categories
        expect(checkboxes[2].element.checked).toBe(false);
    });

    it('does not show category checkboxes when no categories available', () => {
        const noCategWrapper = mount(EditPage, {
            props: { product: baseProduct, categories: [], tags: [] },
        });
        expect(
            noCategWrapper.findAll('input[name="category_ids[]"]'),
        ).toHaveLength(0);
    });
});
