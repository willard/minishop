import { vi } from 'vitest';

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const actual = await importOriginal<any>();
    return {
        ...actual,
        usePage: vi.fn(() => ({
            props: {
                storeSettings: {
                    currency: 'PHP',
                    currencyLocale: 'en-PH',
                    taxRate: 12,
                },
            },
        })),
    };
});
