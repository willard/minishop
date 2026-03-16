import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';
import { useCart } from '@/composables/useCart';
import CartPage from '@/pages/storefront/Cart.vue';

vi.mock('@/composables/useCart', () => ({
    useCart: vi.fn(),
}));

vi.mock('@/composables/usePrice', () => ({
    usePrice: vi.fn(() => ({
        formatPrice: (amount: number) => `$${amount}`,
    })),
}));

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: { name: 'Link', template: '<a href="#"><slot /></a>', props: ['href'] },
}));

vi.mock('@/layouts/StorefrontLayout.vue', () => ({
    default: { name: 'StorefrontLayout', template: '<div><slot /></div>', props: ['categories'] },
}));

vi.mock('@/actions/App/Http/Controllers/Storefront/CheckoutController', () => ({
    create: vi.fn(() => ({ url: '/checkout' })),
}));

vi.mock('@/actions/App/Http/Controllers/Storefront/ProductController', () => ({
    index: vi.fn(() => ({ url: '/products' })),
}));

vi.mock('lucide-vue-next', () => ({
    ShoppingBag: { template: '<svg />' },
    Trash2: { template: '<svg />' },
    ArrowRight: { template: '<svg />' },
    ArrowLeft: { template: '<svg />' },
}));

const mockCartItem = {
    productId: 1,
    variantId: null,
    name: 'Handmade Mug',
    slug: 'handmade-mug',
    sku: 'MUG-001',
    price: 59900,
    quantity: 2,
    image: null,
    variantLabel: null,
};

describe('storefront/Cart', () => {
    let mockUseCart: any;

    beforeEach(() => {
        mockUseCart = {
            cartItems: ref([mockCartItem]),
            itemCount: ref(2),
            subtotal: ref(119800),
            removeItem: vi.fn(),
            updateQuantity: vi.fn(),
            clearCart: vi.fn(),
        };
        (useCart as any).mockReturnValue(mockUseCart);
    });

    it('renders cart items when the cart has items', () => {
        const wrapper = mount(CartPage);

        expect(wrapper.text()).toContain('Handmade Mug');
    });

    it('shows item count in the heading', () => {
        const wrapper = mount(CartPage);

        expect(wrapper.text()).toContain('2 items');
    });

    it('shows formatted subtotal in the order summary', () => {
        const wrapper = mount(CartPage);

        expect(wrapper.text()).toContain('$119800');
    });

    it('calls removeItem when the remove button is clicked', async () => {
        const wrapper = mount(CartPage);

        await wrapper.find('button').trigger('click');

        expect(mockUseCart.removeItem).toHaveBeenCalledWith(
            mockCartItem.productId,
            mockCartItem.variantId,
        );
    });

    it('calls updateQuantity with decremented value when − is clicked', async () => {
        const wrapper = mount(CartPage);

        const buttons = wrapper.findAll('button');
        const decrementButton = buttons.find((b) => b.text() === '−');
        await decrementButton?.trigger('click');

        expect(mockUseCart.updateQuantity).toHaveBeenCalledWith(
            mockCartItem.productId,
            mockCartItem.variantId,
            mockCartItem.quantity - 1,
        );
    });

    it('calls updateQuantity with incremented value when + is clicked', async () => {
        const wrapper = mount(CartPage);

        const buttons = wrapper.findAll('button');
        const incrementButton = buttons.find((b) => b.text() === '+');
        await incrementButton?.trigger('click');

        expect(mockUseCart.updateQuantity).toHaveBeenCalledWith(
            mockCartItem.productId,
            mockCartItem.variantId,
            mockCartItem.quantity + 1,
        );
    });

    it('shows the variant label when present', () => {
        mockUseCart.cartItems = ref([{ ...mockCartItem, variantLabel: 'Red / Large' }]);
        (useCart as any).mockReturnValue(mockUseCart);

        const wrapper = mount(CartPage);

        expect(wrapper.text()).toContain('Red / Large');
    });

    describe('empty cart', () => {
        beforeEach(() => {
            mockUseCart.cartItems = ref([]);
            mockUseCart.itemCount = ref(0);
            mockUseCart.subtotal = ref(0);
            (useCart as any).mockReturnValue(mockUseCart);
        });

        it('shows the empty state message', () => {
            const wrapper = mount(CartPage);

            expect(wrapper.text()).toContain('Your bag is empty');
        });

        it('does not render any cart item rows', () => {
            const wrapper = mount(CartPage);

            expect(wrapper.text()).not.toContain('Handmade Mug');
        });
    });
});
