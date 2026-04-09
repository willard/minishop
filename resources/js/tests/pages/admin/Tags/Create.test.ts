import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import CreatePage from '@/pages/admin/Tags/Create.vue';

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
    const store = vi.fn(() => ({
        url: '/dashboard/tags',
        method: 'post',
    }));
    store.form = vi.fn(() => ({
        action: '/dashboard/tags',
        method: 'post',
    }));
    return {
        index: vi.fn(() => ({ url: '/dashboard/tags' })),
        create: vi.fn(() => ({ url: '/dashboard/tags/create' })),
        store,
    };
});

describe('admin/Tags/Create', () => {
    it('renders without errors', () => {
        const wrapper = mount(CreatePage);
        expect(wrapper.exists()).toBe(true);
    });

    it('displays the page title', () => {
        const wrapper = mount(CreatePage);
        expect(wrapper.text()).toContain('Add Tag');
    });

    it('has a name input field', () => {
        const wrapper = mount(CreatePage);
        const input = wrapper.find('input[name="name"]');
        expect(input.exists()).toBe(true);
    });

    it('has a description textarea', () => {
        const wrapper = mount(CreatePage);
        const textarea = wrapper.find('textarea[name="description"]');
        expect(textarea.exists()).toBe(true);
    });

    it('has a color input field', () => {
        const wrapper = mount(CreatePage);
        const input = wrapper.find('input[name="color"]');
        expect(input.exists()).toBe(true);
    });

    it('has a color picker', () => {
        const wrapper = mount(CreatePage);
        const picker = wrapper.find('input[type="color"]');
        expect(picker.exists()).toBe(true);
    });

    it('has an active checkbox defaulting to checked', () => {
        const wrapper = mount(CreatePage);
        const checkbox = wrapper.find(
            'input[name="is_active"]',
        ) as unknown as { element: HTMLInputElement };
        expect(checkbox.element.checked).toBe(true);
    });
});
