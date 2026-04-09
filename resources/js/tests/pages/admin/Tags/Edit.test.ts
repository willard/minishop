import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import EditPage from '@/pages/admin/Tags/Edit.vue';

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

vi.mock('@/components/InputError.vue', () => ({
    default: {
        name: 'InputError',
        template: '<span />',
        props: ['message'],
    },
}));

vi.mock('@/components/ui/button', () => ({
    Button: {
        name: 'Button',
        template: '<button><slot /></button>',
        props: ['variant', 'size', 'type', 'disabled'],
    },
}));

vi.mock('@/components/ui/checkbox', () => ({
    Checkbox: {
        name: 'Checkbox',
        template:
            '<input type="checkbox" :id="id" :name="name" :value="value" :checked="defaultValue" />',
        props: ['id', 'name', 'value', 'defaultValue'],
    },
}));

vi.mock('@/components/ui/input', () => ({
    Input: {
        name: 'Input',
        template:
            '<input :id="id" :name="name" :value="defaultValue || modelValue || \'\'" :placeholder="placeholder" />',
        props: ['id', 'name', 'defaultValue', 'modelValue', 'placeholder'],
    },
}));

vi.mock('@/components/ui/label', () => ({
    Label: {
        name: 'Label',
        template: '<label><slot /></label>',
        props: ['for'],
    },
}));

vi.mock('@/actions/App/Http/Controllers/Admin/TagController', () => {
    const update = vi.fn(() => ({
        url: '/dashboard/tags/sale',
        method: 'put',
    }));
    update.form = vi.fn(() => ({
        action: '/dashboard/tags/sale?_method=PUT',
        method: 'post',
    }));
    return {
        index: vi.fn(() => ({ url: '/dashboard/tags' })),
        update,
    };
});

const baseTag = {
    id: 1,
    name: 'Sale',
    slug: 'sale',
    description: 'Products on sale',
    color: '#FF5733',
    is_active: true,
};

describe('admin/Tags/Edit', () => {
    it('renders without errors', () => {
        const wrapper = mount(EditPage, { props: { tag: baseTag } });
        expect(wrapper.exists()).toBe(true);
    });

    it('pre-fills name input with tag name', () => {
        const wrapper = mount(EditPage, { props: { tag: baseTag } });
        const input = wrapper.find('input[name="name"]');
        expect(input.element.value).toBe('Sale');
    });

    it('pre-fills description textarea', () => {
        const wrapper = mount(EditPage, { props: { tag: baseTag } });
        const textarea = wrapper.find('textarea[name="description"]');
        expect(textarea.element.value).toBe('Products on sale');
    });

    it('pre-fills color input with tag color', () => {
        const wrapper = mount(EditPage, { props: { tag: baseTag } });
        const input = wrapper.find('input[name="color"]');
        expect(input.element.value).toBe('#FF5733');
    });

    it('pre-checks is_active checkbox when tag is active', () => {
        const wrapper = mount(EditPage, { props: { tag: baseTag } });
        const checkbox = wrapper.find('input[name="is_active"]') as unknown as {
            element: HTMLInputElement;
        };
        expect(checkbox.element.checked).toBe(true);
    });

    it('leaves is_active unchecked when tag is inactive', () => {
        const wrapper = mount(EditPage, {
            props: { tag: { ...baseTag, is_active: false } },
        });
        const checkbox = wrapper.find('input[name="is_active"]') as unknown as {
            element: HTMLInputElement;
        };
        expect(checkbox.element.checked).toBe(false);
    });

    it('handles null color gracefully', () => {
        const wrapper = mount(EditPage, {
            props: { tag: { ...baseTag, color: null } },
        });
        const input = wrapper.find('input[name="color"]');
        expect(input.element.value).toBe('');
    });
});
