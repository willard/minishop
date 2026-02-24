import EditPage from '@/pages/admin/Categories/Edit.vue';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => ({
    Form: {
        name: 'Form',
        template: '<form><slot :errors="{}" :processing="false" /></form>',
        props: ['action', 'method'],
    },
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: { name: 'Link', template: '<a href="#"><slot /></a>', props: ['href'] },
}));

vi.mock('@/layouts/AppLayout.vue', () => ({
    default: { name: 'AppLayout', template: '<div><slot /></div>', props: ['breadcrumbs'] },
}));

vi.mock('@/components/ui/checkbox', () => ({
    Checkbox: {
        name: 'Checkbox',
        // Maps defaultValue → checked so we can assert .checked in the DOM.
        // Tests will FAIL if the code regresses to :default-checked.
        template: '<input type="checkbox" :id="id" :name="name" :value="value" :checked="defaultValue" />',
        props: ['id', 'name', 'value', 'defaultValue'],
    },
}));

vi.mock('@/components/ui/button', () => ({
    Button: { name: 'Button', template: '<button><slot /></button>', props: ['variant', 'size', 'type', 'disabled'] },
}));

vi.mock('@/components/ui/label', () => ({
    Label: { name: 'Label', template: '<label><slot /></label>', props: ['for'] },
}));

vi.mock('@/actions/App/Http/Controllers/Admin/CategoryController', () => {
    const update = vi.fn(() => ({ url: '/dashboard/categories/electronics', method: 'put' }));
    update.form = vi.fn(() => ({
        action: '/dashboard/categories/electronics?_method=PUT',
        method: 'post',
    }));
    return {
        index: vi.fn(() => ({ url: '/dashboard/categories' })),
        update,
    };
});

const baseCategory = {
    id: 1,
    name: 'Electronics',
    slug: 'electronics',
    description: 'Electronic products',
    sort_order: 3,
    is_active: true,
    parent_id: 2,
};

const parentCategories = [
    { id: 2, name: 'Tech' },
    { id: 3, name: 'Gadgets' },
];

describe('admin/Categories/Edit', () => {
    let wrapper: ReturnType<typeof mount>;

    beforeEach(() => {
        wrapper = mount(EditPage, {
            props: { category: baseCategory, parentCategories },
        });
    });

    it('renders without errors', () => {
        expect(wrapper.exists()).toBe(true);
    });

    it('pre-fills name input with category name', () => {
        const input = wrapper.find('input[name="name"]');
        expect(input.element.value).toBe('Electronics');
    });

    it('pre-fills description textarea with category description', () => {
        const textarea = wrapper.find('textarea[name="description"]');
        expect(textarea.element.value).toBe('Electronic products');
    });

    it('pre-fills sort order input', () => {
        const input = wrapper.find('input[name="sort_order"]');
        expect(input.element.value).toBe('3');
    });

    it('pre-selects the correct parent category in select', () => {
        const select = wrapper.find('select[name="parent_id"]') as { element: HTMLSelectElement };
        // Value is compared as a string since DOM select values are always strings
        expect(select.element.value).toBe('2');
    });

    it('pre-checks is_active checkbox when category is active', () => {
        const checkbox = wrapper.find('input[name="is_active"]') as { element: HTMLInputElement };
        expect(checkbox.element.checked).toBe(true);
    });

    it('leaves is_active unchecked when category is inactive', () => {
        const inactiveWrapper = mount(EditPage, {
            props: { category: { ...baseCategory, is_active: false }, parentCategories },
        });
        const checkbox = inactiveWrapper.find('input[name="is_active"]') as { element: HTMLInputElement };
        expect(checkbox.element.checked).toBe(false);
    });

    it('hides parent category select when no parent categories available', () => {
        const noParentsWrapper = mount(EditPage, {
            props: { category: baseCategory, parentCategories: [] },
        });
        expect(noParentsWrapper.find('select[name="parent_id"]').exists()).toBe(false);
    });

    it('displays an empty parent option alongside parent choices', () => {
        const select = wrapper.find('select[name="parent_id"]');
        const options = select.findAll('option');
        // First option is always "None (top-level category)"
        expect(options[0].element.value).toBe('');
        expect(options).toHaveLength(3); // "" + Tech + Gadgets
    });
});
