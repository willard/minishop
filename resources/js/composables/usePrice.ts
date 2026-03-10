import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { formatPrice as baseFormatPrice } from '@/lib/utils';

export function usePrice() {
    const page = usePage<{
        storeSettings: {
            currency: string;
            currencyLocale: string;
        };
    }>();

    const currency = computed(
        () => page.props?.storeSettings?.currency ?? 'PHP',
    );
    const locale = computed(
        () => page.props?.storeSettings?.currencyLocale ?? 'en-PH',
    );

    function formatPrice(cents: number): string {
        return baseFormatPrice(cents, currency.value, locale.value);
    }

    return {
        formatPrice,
        currency,
        locale,
    };
}
