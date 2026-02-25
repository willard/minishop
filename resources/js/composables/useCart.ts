import { useLocalStorage } from '@vueuse/core';
import { computed } from 'vue';
import type { CartItem } from '@/types/storefront';

const cartItems = useLocalStorage<CartItem[]>('minishop_cart', []);

export function useCart() {
    const itemCount = computed(() => cartItems.value.reduce((sum, item) => sum + item.quantity, 0));

    const subtotal = computed(() => cartItems.value.reduce((sum, item) => sum + item.price * item.quantity, 0));

    function addItem(item: Omit<CartItem, 'quantity'> & { quantity?: number }): void {
        const existing = cartItems.value.find(
            (i) => i.productId === item.productId && i.variantId === item.variantId,
        );

        if (existing) {
            existing.quantity += item.quantity ?? 1;
        } else {
            cartItems.value = [...cartItems.value, { ...item, quantity: item.quantity ?? 1 }];
        }
    }

    function updateQuantity(productId: number, variantId: number | null, quantity: number): void {
        if (quantity <= 0) {
            removeItem(productId, variantId);

            return;
        }

        const item = cartItems.value.find(
            (i) => i.productId === productId && i.variantId === variantId,
        );

        if (item) {
            item.quantity = quantity;
        }
    }

    function removeItem(productId: number, variantId: number | null): void {
        cartItems.value = cartItems.value.filter(
            (i) => !(i.productId === productId && i.variantId === variantId),
        );
    }

    function clearCart(): void {
        cartItems.value = [];
    }

    return { cartItems, itemCount, subtotal, addItem, updateQuantity, removeItem, clearCart };
}
