import { useLocalStorage } from '@vueuse/core';
import { computed, ref } from 'vue';
import type { CartItem } from '@/types/storefront';

const cartItems = useLocalStorage<CartItem[]>('minishop_cart', []);
const lastAddedItem = ref<CartItem | null>(null);
const isDrawerOpen = ref(false);

// Migrate stale image paths stored before the /storage/ prefix was added
if (
    cartItems.value.some(
        (item) =>
            item.image &&
            !item.image.startsWith('/') &&
            !item.image.startsWith('http'),
    )
) {
    cartItems.value = cartItems.value.map((item) => ({
        ...item,
        image:
            item.image &&
            !item.image.startsWith('/') &&
            !item.image.startsWith('http')
                ? `/storage/${item.image}`
                : item.image,
    }));
}

export function useCart() {
    const itemCount = computed(() =>
        cartItems.value.reduce((sum, item) => sum + item.quantity, 0),
    );

    const subtotal = computed(() =>
        cartItems.value.reduce(
            (sum, item) => sum + item.price * item.quantity,
            0,
        ),
    );

    function addItem(
        item: Omit<CartItem, 'quantity'> & { quantity?: number },
    ): void {
        const existing = cartItems.value.find(
            (i) =>
                i.productId === item.productId &&
                i.variantId === item.variantId,
        );

        let newItem: CartItem;

        if (existing) {
            existing.quantity += item.quantity ?? 1;
            newItem = { ...existing };
        } else {
            newItem = { ...item, quantity: item.quantity ?? 1 };
            cartItems.value = [...cartItems.value, newItem];
        }

        lastAddedItem.value = newItem;
        isDrawerOpen.value = true;
    }

    function updateQuantity(
        productId: number,
        variantId: number | null,
        quantity: number,
    ): void {
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

    function openDrawer(): void {
        isDrawerOpen.value = true;
    }

    function closeDrawer(): void {
        isDrawerOpen.value = false;
    }

    return {
        cartItems,
        itemCount,
        subtotal,
        addItem,
        updateQuantity,
        removeItem,
        clearCart,
        lastAddedItem,
        isDrawerOpen,
        openDrawer,
        closeDrawer,
    };
}
