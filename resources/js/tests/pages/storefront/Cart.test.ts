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
    X: { template: '<svg />' },
    ArrowRight: { template: '<svg />' },
    ArrowLeft: { template: '<svg />' },
    Lock: { template: '<svg />' },
    Minus: { template: '<svg />' },
    Plus: { template: '<svg />' },
    Package: { template: '<svg />' },
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

    it('renders the page heading', () => {
        const wrapper = mount(CartPage);

        expect(wrapper.text()).toContain('Your Bag');
    });

    it('renders cart items when the cart has items', () => {
        const wrapper = mount(CartPage);

        expect(wrapper.text()).toContain('Handmade Mug');
    });

    it('shows item count in the heading', () => {
        const wrapper = mount(CartPage);

        expect(wrapper.text()).toContain('2');
        expect(wrapper.text()).toContain('items');
    });

    it('shows formatted subtotal in the order summary', () => {
        const wrapper = mount(CartPage);

        expect(wrapper.text()).toContain('$119800');
    });

    it('shows free shipping progress when below threshold', () => {
        const wrapper = mount(CartPage);

        expect(wrapper.text()).toContain('free shipping');
    });

    it('shows free shipping unlocked message when at or above threshold', () => {
        mockUseCart.subtotal = ref(25000);
        (useCart as any).mockReturnValue(mockUseCart);

        const wrapper = mount(CartPage);

        expect(wrapper.text()).toContain("You've unlocked free shipping");
    });

    it('calls removeItem when the remove button is clicked', async () => {
        const wrapper = mount(CartPage);

        const removeButtons = wrapper.findAll('button[aria-label="Remove item"]');
        await removeButtons[0].trigger('click');

        expect(mockUseCart.removeItem).toHaveBeenCalledWith(
            mockCartItem.productId,
            mockCartItem.variantId,
        );
    });

    it('calls updateQuantity with incremented value when + is clicked', async () => {
        const wrapper = mount(CartPage);

        const quantityControls = wrapper.findAll('.rounded-full.border');
        const plusBtn = quantityControls[0].findAll('button')[1];
        await plusBtn.trigger('click');

        expect(mockUseCart.updateQuantity).toHaveBeenCalledWith(
            mockCartItem.productId,
            mockCartItem.variantId,
            mockCartItem.quantity + 1,
        );
    });

    it('calls clearCart when the clear bag button is clicked', async () => {
        const wrapper = mount(CartPage);

        const clearButton = wrapper.findAll('button').find((b) => b.text() === 'Clear bag');
        await clearButton?.trigger('click');

        expect(mockUseCart.clearCart).toHaveBeenCalled();
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

        it('shows a browse products link', () => {
            const wrapper = mount(CartPage);

            expect(wrapper.text()).toContain('Browse Products');
        });
    });
});
