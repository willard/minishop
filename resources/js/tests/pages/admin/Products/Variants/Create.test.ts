import CreateVariantPage from '@/pages/admin/Products/Variants/Create.vue';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: { name: 'Link', template: '<a href="#"><slot /></a>', props: ['href'] },
    useForm: vi.fn(() => ({
        sku: '',
        price: null,
        stock_quantity: 0,
        options: [{ name: '', value: '' }],
        is_active: true,
        processing: false,
        errors: {},
        post: vi.fn(),
        put: vi.fn(),
    })),
}));

vi.mock('@/layouts/AppLayout.vue', () => ({
    default: { name: 'AppLayout', template: '<div><slot /></div>', props: ['breadcrumbs'] },
}));

vi.mock('@/components/ui/button', () => ({
    Button: { name: 'Button', template: '<button><slot /></button>', props: ['variant', 'size', 'type', 'disabled'] },
}));

vi.mock('@/components/ui/input', () => ({
    Input: { name: 'Input', template: '<input />', props: ['id', 'modelValue', 'type', 'min', 'placeholder', 'class'] },
}));

vi.mock('@/components/ui/label', () => ({
    Label: { name: 'Label', template: '<label><slot /></label>', props: ['for'] },
}));

vi.mock('@/components/ui/checkbox', () => ({
    Checkbox: { name: 'Checkbox', template: '<input type="checkbox" />', props: ['id', 'checked'] },
}));

vi.mock('@/components/InputError.vue', () => ({
    default: { name: 'InputError', template: '<span />', props: ['message'] },
}));

vi.mock('@/actions/App/Http/Controllers/Admin/ProductController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/products' })),
    show: vi.fn((product: { slug: string }) => ({ url: `/dashboard/products/${product.slug}` })),
}));

vi.mock('@/actions/App/Http/Controllers/Admin/ProductVariantController', () => ({
    store: vi.fn((product: { slug: string }) => ({ url: `/dashboard/products/${product.slug}/variants` })),
}));

const baseProduct = {
    id: 1,
    name: 'Blue T-Shirt',
    slug: 'blue-t-shirt',
};

describe('admin/Products/Variants/Create', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        wrapper = mount(CreateVariantPage, {
            props: { product: baseProduct },
        });
    });

    it('renders without errors', () => {
        expect(wrapper.exists()).toBe(true);
    });

    it('displays the product name in the header', () => {
        expect(wrapper.text()).toContain('Blue T-Shirt');
    });

    it('displays the Add Variant heading', () => {
        expect(wrapper.text()).toContain('Add Variant');
    });

    it('renders the options section', () => {
        expect(wrapper.text()).toContain('Options');
    });

    it('renders the stock quantity field label', () => {
        expect(wrapper.text()).toContain('Stock Quantity');
    });

    it('renders the SKU field label', () => {
        expect(wrapper.text()).toContain('SKU');
    });

    it('renders the price override field label', () => {
        expect(wrapper.text()).toContain('Price Override');
    });
});
