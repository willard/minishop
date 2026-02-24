import CreateOptionPage from '@/pages/admin/Products/Options/Create.vue';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: { name: 'Link', template: '<a :href="href"><slot /></a>', props: ['href'] },
    useForm: vi.fn(() => ({
        name: '',
        values: [''],
        processing: false,
        errors: {},
        post: vi.fn(),
    })),
}));

vi.mock('@/layouts/AppLayout.vue', () => ({
    default: { name: 'AppLayout', template: '<div><slot /></div>', props: ['breadcrumbs'] },
}));

vi.mock('@/components/ui/button', () => ({
    Button: { name: 'Button', template: '<button @click="$emit(\'click\')"><slot /></button>', props: ['variant', 'size', 'type', 'disabled'] },
}));

vi.mock('@/components/ui/input', () => ({
    Input: { name: 'Input', template: '<input />', props: ['id', 'modelValue', 'placeholder', 'class', 'autofocus'] },
}));

vi.mock('@/components/ui/label', () => ({
    Label: { name: 'Label', template: '<label><slot /></label>', props: ['for'] },
}));

vi.mock('@/components/InputError.vue', () => ({
    default: { name: 'InputError', template: '<span />', props: ['message'] },
}));

vi.mock('@/actions/App/Http/Controllers/Admin/ProductController', () => ({
    index: vi.fn(() => ({ url: '/dashboard/products' })),
    show: vi.fn((product: { slug: string }) => ({ url: `/dashboard/products/${product.slug}` })),
}));

vi.mock('@/actions/App/Http/Controllers/Admin/ProductOptionController', () => ({
    store: vi.fn((product: { slug: string }) => ({ url: `/dashboard/products/${product.slug}/options` })),
}));

const baseProduct = {
    id: 1,
    name: 'Blue T-Shirt',
    slug: 'blue-t-shirt',
};

describe('admin/Products/Options/Create', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        wrapper = mount(CreateOptionPage, {
            props: { product: baseProduct },
        });
    });

    it('renders without errors', () => {
        expect(wrapper.exists()).toBe(true);
    });

    it('displays the Add Option Type heading', () => {
        expect(wrapper.text()).toContain('Add Option Type');
    });

    it('displays the product name in the header', () => {
        expect(wrapper.text()).toContain('Blue T-Shirt');
    });

    it('renders the Option Name field label', () => {
        expect(wrapper.text()).toContain('Option Name');
    });

    it('renders the Values label', () => {
        expect(wrapper.text()).toContain('Values');
    });

    it('renders an Add value button', () => {
        expect(wrapper.text()).toContain('Add value');
    });

    it('renders the submit button', () => {
        expect(wrapper.text()).toContain('Add Option Type');
    });

    it('renders a Cancel link pointing back to the product show page', () => {
        const links = wrapper.findAll('a');
        const hrefs = links.map((l) => l.attributes('href'));
        expect(hrefs).toContain('/dashboard/products/blue-t-shirt');
    });
});
