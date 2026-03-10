import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';
import { useCart } from '@/composables/useCart';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';

// Mock useCart
vi.mock('@/composables/useCart', () => ({
    useCart: vi.fn(),
}));

// Mock Inertia components and functions
vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const actual = await importOriginal<any>();
    return {
        ...actual,
        Head: { template: '<div><slot /></div>' },
        Link: { template: '<a><slot /></a>' },
    };
});

describe('StorefrontLayout', () => {
    let mockUseCart: any;
    const isDrawerOpenRef = ref(false);

    beforeEach(() => {
        isDrawerOpenRef.value = false;
        mockUseCart = {
            cartItems: ref([]),
            itemCount: ref(0),
            subtotal: ref(0),
            lastAddedItem: ref(null),
            isDrawerOpen: isDrawerOpenRef,
            openDrawer: vi.fn(() => (isDrawerOpenRef.value = true)),
            closeDrawer: vi.fn(() => (isDrawerOpenRef.value = false)),
            addItem: vi.fn(),
            removeItem: vi.fn(),
            updateQuantity: vi.fn(),
            clearCart: vi.fn(),
        };
        (useCart as any).mockReturnValue(mockUseCart);
    });

    it('renders the cart drawer', () => {
        const wrapper = mount(StorefrontLayout);
        expect(wrapper.findComponent({ name: 'CartDrawer' }).exists()).toBe(
            true,
        );
    });

    it('passes isDrawerOpen state to CartDrawer', async () => {
        const wrapper = mount(StorefrontLayout);
        const drawer = wrapper.getComponent({ name: 'CartDrawer' });

        expect(drawer.props('isOpen')).toBe(false);

        isDrawerOpenRef.value = true;
        await wrapper.vm.$nextTick();

        expect(drawer.props('isOpen')).toBe(true);
    });

    it('calls openDrawer when cart button is clicked', async () => {
        const wrapper = mount(StorefrontLayout);
        const cartButton = wrapper.find('button.relative'); // The shopping bag button

        await cartButton.trigger('click');

        expect(mockUseCart.openDrawer).toHaveBeenCalled();
        expect(isDrawerOpenRef.value).toBe(true);
    });
});
