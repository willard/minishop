import { onUnmounted, ref, watch } from 'vue';
import type { ComputedRef, Ref } from 'vue';
import TaxPreviewController from '@/actions/Minishop/Http/Controllers/Storefront/TaxPreviewController';
import { getCsrfToken } from '@/lib/csrf';

export interface TaxBreakdownLine {
    name: string;
    name_fr: string | null;
    rate: number;
    amount_cents: number;
}

export interface TaxPreviewResult {
    mode: 'flat_rate' | 'zone_based';
    zone_name: string | null;
    breakdown: TaxBreakdownLine[];
    total_tax_cents: number;
    effective_rate: number;
}

export function useTaxPreview(
    country: Ref<string> | ComputedRef<string>,
    province: Ref<string> | ComputedRef<string>,
    subtotalCents: Ref<number> | ComputedRef<number>,
) {
    const result = ref<TaxPreviewResult | null>(null);
    const isLoading = ref(false);
    const fetchError = ref<string | null>(null);

    let debounceTimer: ReturnType<typeof setTimeout> | null = null;
    let abortController: AbortController | null = null;

    async function fetchTaxPreview(): Promise<void> {
        const currentCountry = country.value?.trim().toUpperCase();
        const currentSubtotal = subtotalCents.value;

        // Do not fetch when inputs are empty or subtotal is zero
        if (!currentCountry || currentSubtotal <= 0) {
            result.value = null;
            return;
        }

        // Cancel any in-flight request before starting a new one
        abortController?.abort();
        abortController = new AbortController();

        isLoading.value = true;
        fetchError.value = null;

        try {
            const body: Record<string, unknown> = {
                country: currentCountry,
                subtotal: currentSubtotal,
            };

            const rawProvince = province.value?.trim().toUpperCase();
            if (rawProvince && rawProvince.length === 2) {
                body.province_code = rawProvince;
            }

            const response = await fetch(TaxPreviewController.url(), {
                method: 'POST',
                signal: abortController.signal,
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify(body),
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            result.value = data.tax ?? null;
        } catch (err) {
            if (err instanceof Error && err.name === 'AbortError') {
                // Intentionally cancelled — do not set error state
                return;
            }
            fetchError.value = 'Could not load tax preview.';
        } finally {
            isLoading.value = false;
        }
    }

    watch(
        [country, province, subtotalCents],
        () => {
            if (debounceTimer !== null) {
                clearTimeout(debounceTimer);
            }
            // Cancel in-flight request immediately when inputs change —
            // prevents stale response race conditions
            abortController?.abort();
            debounceTimer = setTimeout(fetchTaxPreview, 500);
        },
        { deep: true },
    );

    onUnmounted(() => {
        if (debounceTimer !== null) {
            clearTimeout(debounceTimer);
        }
        abortController?.abort();
    });

    return { result, isLoading, fetchError };
}
