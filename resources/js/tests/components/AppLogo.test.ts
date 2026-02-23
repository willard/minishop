import AppLogo from '@/components/AppLogo.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('AppLogo', () => {
    it('renders without errors', () => {
        const wrapper = mount(AppLogo);
        expect(wrapper.exists()).toBe(true);
    });

    it('renders the app name text', () => {
        const wrapper = mount(AppLogo);
        expect(wrapper.text()).toContain('Laravel Starter Kit');
    });
});
