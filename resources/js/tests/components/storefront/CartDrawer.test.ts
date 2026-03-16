import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';
import CartDrawer from '@/components/storefront/CartDrawer.vue';
import { useCart } from '@/composables/useCart';

// Mock useCart
vi.mock('@/composables/useCart', () => ({
    useCart: vi.fn(),
}));

const mockCartItems = [
    {
        productId: 1,
        variantId: null,
        name: 'Handmade Mug',
        slug: 'handmade-mug',
        sku: 'MUG-001',
        price: 59900,
        quantity: 1,
        image: null,
        variantLabel: null,
    },
];

describe('CartDrawer', () => {
    let mockUseCart: any;
    const cartItemsRef = ref<any[]>([]);
    const itemCountRef = ref(0);
    const subtotalRef = ref(0);

    beforeEach(() => {
        cartItemsRef.value = [];
        itemCountRef.value = 0;
        subtotalRef.value = 0;

        mockUseCart = {
            cartItems: cartItemsRef,
            itemCount: itemCountRef,
            subtotal: subtotalRef,
            removeItem: vi.fn(),
            updateQuantity: vi.fn(),
        };
        (useCart as any).mockReturnValue(mockUseCart);
    });

    it('renders nothing when not open (handled by parent v-if usually, but we test inner content)', () => {
        const wrapper = mount(CartDrawer, {
            props: { isOpen: false },
        });
        expect(wrapper.find('h2').exists()).toBe(false);
    });

    it('renders "empty" state when cart is empty', () => {
        const wrapper = mount(CartDrawer, {
            props: { isOpen: true },
        });
        expect(wrapper.text()).toContain('Your bag is empty');
    });

    it('renders cart items when present', () => {
        cartItemsRef.value = mockCartItems;
        itemCountRef.value = 1;
        subtotalRef.value = 59900;

        const wrapper = mount(CartDrawer, {
            props: { isOpen: true },
        });

        expect(wrapper.text()).toContain('Handmade Mug');
        expect(wrapper.text()).toContain('$599.00');
    });

    it('emits close event when close button is clicked', async () => {
        const wrapper = mount(CartDrawer, {
            props: { isOpen: true },
        });

        const closeButton = wrapper.find('button.rounded-full.p-2');
        await closeButton.trigger('click');

        expect(wrapper.emitted()).toHaveProperty('close');
    });

    it('calls removeItem when trash icon is clicked', async () => {
        cartItemsRef.value = mockCartItems;
        const wrapper = mount(CartDrawer, {
            props: { isOpen: true },
        });

        const removeButton = wrapper.find(
            'button.transition-opacity.hover\\:opacity-60',
        );
        await removeButton.trigger('click');

        expect(mockUseCart.removeItem).toHaveBeenCalledWith(1, null);
    });

    it('calls updateQuantity when +/- buttons are clicked', async () => {
        cartItemsRef.value = mockCartItems;
        const wrapper = mount(CartDrawer, {
            props: { isOpen: true },
        });

        const buttons = wrapper.findAll('button.flex.size-7');
        // buttons[0] is -, buttons[1] is +
        await buttons[1].trigger('click'); // Increment

        expect(mockUseCart.updateQuantity).toHaveBeenCalledWith(1, null, 2);

        await buttons[0].trigger('click'); // Decrement
        expect(mockUseCart.updateQuantity).toHaveBeenCalledWith(1, null, 0);
    });

    it('shows a View Cart link in the footer when cart has items', () => {
        cartItemsRef.value = mockCartItems;
        itemCountRef.value = 1;
        subtotalRef.value = 59900;

        const wrapper = mount(CartDrawer, {
            props: { isOpen: true },
        });

        expect(wrapper.text()).toContain('View Cart');
    });
});
