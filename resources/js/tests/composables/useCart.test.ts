import { beforeEach, describe, expect, it } from 'vitest';
import { useCart } from '@/composables/useCart';
import type { CartItem } from '@/types/storefront';

const baseItem: Omit<CartItem, 'quantity'> = {
    productId: 1,
    variantId: null,
    name: 'Handmade Mug',
    slug: 'handmade-mug',
    sku: 'MUG-001',
    price: 59900,
    image: null,
    variantLabel: null,
};

beforeEach(() => {
    localStorage.clear();
    // Reset the singleton reactive ref so tests are isolated
    const { clearCart } = useCart();
    clearCart();
});

describe('useCart', () => {
    it('starts with an empty cart', () => {
        const { cartItems } = useCart();
        expect(cartItems.value).toHaveLength(0);
    });

    it('adds an item to the cart', () => {
        const { addItem, cartItems } = useCart();
        addItem(baseItem);
        expect(cartItems.value).toHaveLength(1);
        expect(cartItems.value[0].name).toBe('Handmade Mug');
    });

    it('defaults quantity to 1 when not specified', () => {
        const { addItem, cartItems } = useCart();
        addItem(baseItem);
        expect(cartItems.value[0].quantity).toBe(1);
    });

    it('respects provided quantity when adding', () => {
        const { addItem, cartItems } = useCart();
        addItem({ ...baseItem, quantity: 3 });
        expect(cartItems.value[0].quantity).toBe(3);
    });

    it('increments quantity when same product+variant added twice', () => {
        const { addItem, cartItems } = useCart();
        addItem(baseItem);
        addItem(baseItem);
        expect(cartItems.value).toHaveLength(1);
        expect(cartItems.value[0].quantity).toBe(2);
    });

    it('treats same product with different variants as separate items', () => {
        const { addItem, cartItems } = useCart();
        addItem({ ...baseItem, variantId: 1, variantLabel: 'S' });
        addItem({ ...baseItem, variantId: 2, variantLabel: 'M' });
        expect(cartItems.value).toHaveLength(2);
    });

    it('removes an item from the cart', () => {
        const { addItem, removeItem, cartItems } = useCart();
        addItem(baseItem);
        removeItem(baseItem.productId, baseItem.variantId);
        expect(cartItems.value).toHaveLength(0);
    });

    it('updates item quantity', () => {
        const { addItem, updateQuantity, cartItems } = useCart();
        addItem(baseItem);
        updateQuantity(baseItem.productId, baseItem.variantId, 5);
        expect(cartItems.value[0].quantity).toBe(5);
    });

    it('removes item when quantity updated to zero', () => {
        const { addItem, updateQuantity, cartItems } = useCart();
        addItem(baseItem);
        updateQuantity(baseItem.productId, baseItem.variantId, 0);
        expect(cartItems.value).toHaveLength(0);
    });

    it('clears the entire cart', () => {
        const { addItem, clearCart, cartItems } = useCart();
        addItem(baseItem);
        addItem({ ...baseItem, productId: 2, name: 'Bowl' });
        clearCart();
        expect(cartItems.value).toHaveLength(0);
    });

    it('computes correct item count', () => {
        const { addItem, itemCount } = useCart();
        addItem({ ...baseItem, quantity: 2 });
        addItem({ ...baseItem, productId: 2, name: 'Bowl', quantity: 3 });
        expect(itemCount.value).toBe(5);
    });

    it('computes correct subtotal', () => {
        const { addItem, subtotal } = useCart();
        addItem({ ...baseItem, price: 10000, quantity: 2 });
        expect(subtotal.value).toBe(20000);
    });

    it('subtotal accounts for multiple different items', () => {
        const { addItem, subtotal } = useCart();
        addItem({ ...baseItem, price: 10000, quantity: 1 });
        addItem({ ...baseItem, productId: 2, name: 'Bowl', price: 5000, quantity: 3 });
        expect(subtotal.value).toBe(25000);
    });
});
