import { onUnmounted, ref, watch } from 'vue';
import type { Ref } from 'vue';
import { getCsrfToken } from '@/lib/csrf';
import type { CartItem, ShippingRate } from '@/types/storefront';

export function useShippingRates(
    postcode: Ref<string>,
    country: Ref<string>,
    cartItems: Ref<CartItem[]>,
) {
    const rates = ref<ShippingRate[]>([]);
    const isLoading = ref(false);
    const fetchError = ref<string | null>(null);

    let debounceTimer: ReturnType<typeof setTimeout> | null = null;
    let abortController: AbortController | null = null;

    async function fetchRates(): Promise<void> {
        const currentPostcode = postcode.value.trim();
        const currentCountry = country.value.trim();
        const items = cartItems.value;

        if (!currentPostcode || !currentCountry || items.length === 0) {
            return;
        }

        abortController?.abort();
        abortController = new AbortController();

        isLoading.value = true;
        fetchError.value = null;

        try {
            const response = await fetch('/checkout/shipping-rates', {
                method: 'POST',
                signal: abortController.signal,
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({
                    postcode: currentPostcode,
                    country: currentCountry,
                    items: items.map((item) => ({
                        product_id: item.productId,
                        variant_id: item.variantId,
                        quantity: item.quantity,
                    })),
                }),
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            rates.value = data.rates ?? [];
        } catch (err) {
            if (err instanceof Error && err.name === 'AbortError') {
                return;
            }
            fetchError.value = 'Could not load shipping rates. Flat rates are still available.';
        } finally {
            isLoading.value = false;
        }
    }

    watch(
        [postcode, country, cartItems],
        () => {
            if (debounceTimer !== null) {
                clearTimeout(debounceTimer);
            }
            debounceTimer = setTimeout(fetchRates, 600);
        },
        { deep: true },
    );

    onUnmounted(() => {
        if (debounceTimer !== null) {
            clearTimeout(debounceTimer);
        }
        abortController?.abort();
    });

    return { rates, isLoading, fetchError };
}
