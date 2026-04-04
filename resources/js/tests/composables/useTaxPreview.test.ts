import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick, ref } from 'vue';

vi.mock('@/lib/csrf', () => ({
    getCsrfToken: () => 'test-csrf-token',
}));

const mockFetch = vi.fn();
vi.stubGlobal('fetch', mockFetch);

function makeResponse(body: object, status = 200) {
    return Promise.resolve({
        ok: status >= 200 && status < 300,
        status,
        json: () => Promise.resolve(body),
    });
}

const ontarioTax = {
    tax: {
        mode: 'zone_based',
        zone_name: 'Ontario',
        breakdown: [{ name: 'HST', name_fr: null, rate: 13, amount_cents: 1300 }],
        total_tax_cents: 1300,
        effective_rate: 0.13,
    },
};

describe('useTaxPreview', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.useFakeTimers();
    });

    it('returns isLoading false and result null initially', async () => {
        const { useTaxPreview } = await import('@/composables/useTaxPreview');
        const country = ref('CA');
        const province = ref('ON');
        const subtotal = ref(10000);

        const { result, isLoading } = useTaxPreview(country, province, subtotal);

        expect(isLoading.value).toBe(false);
        expect(result.value).toBeNull();
    });

    it('does not fetch when country is empty', async () => {
        const { useTaxPreview } = await import('@/composables/useTaxPreview');
        const country = ref('');
        const province = ref('ON');
        const subtotal = ref(10000);

        useTaxPreview(country, province, subtotal);
        vi.advanceTimersByTime(600);
        await vi.runAllTimersAsync();

        expect(mockFetch).not.toHaveBeenCalled();
    });

    it('does not fetch when subtotal is zero', async () => {
        const { useTaxPreview } = await import('@/composables/useTaxPreview');
        const country = ref('CA');
        const province = ref('ON');
        const subtotal = ref(0);

        useTaxPreview(country, province, subtotal);
        vi.advanceTimersByTime(600);
        await vi.runAllTimersAsync();

        expect(mockFetch).not.toHaveBeenCalled();
    });

    it('sets isLoading true while fetching and populates result on success', async () => {
        mockFetch.mockReturnValue(makeResponse(ontarioTax));

        const { useTaxPreview } = await import('@/composables/useTaxPreview');
        // Start with empty country so changing it actually triggers the watch
        const country = ref('');
        const province = ref('ON');
        const subtotal = ref(10000);

        const { result, isLoading } = useTaxPreview(country, province, subtotal);

        // Trigger the watch by changing to a real value
        country.value = 'CA';
        await nextTick(); // Vue flushes watch; debounce timer is scheduled
        vi.advanceTimersByTime(600); // debounce fires → fetchTaxPreview called
        await vi.runAllTimersAsync();

        expect(mockFetch).toHaveBeenCalledOnce();
        expect(isLoading.value).toBe(false);
        expect(result.value).not.toBeNull();
        expect(result.value?.total_tax_cents).toBe(1300);
        expect(result.value?.breakdown[0].name).toBe('HST');
    });

    it('sets fetchError on HTTP error', async () => {
        mockFetch.mockReturnValue(makeResponse({}, 500));

        const { useTaxPreview } = await import('@/composables/useTaxPreview');
        const country = ref('');
        const province = ref('ON');
        const subtotal = ref(10000);

        const { fetchError } = useTaxPreview(country, province, subtotal);

        country.value = 'CA';
        await nextTick();
        vi.advanceTimersByTime(600);
        await vi.runAllTimersAsync();

        expect(fetchError.value).toBe('Could not load tax preview.');
    });

    it('cancels in-flight request when inputs change before debounce fires', async () => {
        let abortSignalAborted = false;
        mockFetch.mockImplementation((_url: string, opts: RequestInit) => {
            opts.signal?.addEventListener('abort', () => {
                abortSignalAborted = true;
            });
            // Return a promise that never resolves (simulating slow request)
            return new Promise(() => {});
        });

        const { useTaxPreview } = await import('@/composables/useTaxPreview');
        const country = ref('');
        const province = ref('ON');
        const subtotal = ref(10000);

        useTaxPreview(country, province, subtotal);

        // Trigger the watch and fire the debounce so fetch() is in-flight
        country.value = 'CA';
        await nextTick(); // watch fires, schedules debounce
        vi.advanceTimersByTime(600); // debounce fires, fetchTaxPreview() starts
        await nextTick(); // let the async function run up to await fetch()
        await Promise.resolve();

        // Change input — watch fires and immediately aborts the in-flight controller
        province.value = 'QC';
        await nextTick();

        expect(abortSignalAborted).toBe(true);
    });
});
