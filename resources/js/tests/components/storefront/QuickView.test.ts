import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';
import QuickView from '@/components/storefront/QuickView.vue';
import { useCart } from '@/composables/useCart';

// Mock useCart
vi.mock('@/composables/useCart', () => ({
    useCart: vi.fn(),
}));

// Mock Inertia
vi.mock('@inertiajs/vue3', () => ({
    Link: { template: '<a><slot /></a>' },
    usePage: vi.fn(() => ({
        props: { storeSettings: { currency: 'PHP' } },
    })),
}));

// Mock Actions
vi.mock('@/actions/App/Http/Controllers/Storefront/ProductController', () => ({
    show: vi.fn(() => ({ url: '/products/test' })),
}));

const mockProduct = {
    id: 1,
    name: 'Handmade Mug',
    slug: 'handmade-mug',
    sku: 'MUG-001',
    price: 59900,
    stock_quantity: 10,
    description: 'A beautiful handmade mug.',
    images: [{ id: 1, path: '/img.jpg', alt_text: 'Mug' }],
    categories: [{ id: 1, name: 'Kitchen' }],
    options: [
        {
            id: 1,
            name: 'Size',
            values: [
                { id: 1, value: 'Small' },
                { id: 2, value: 'Large' },
            ],
        },
    ],
    variants: [
        {
            id: 1,
            sku: 'MUG-S',
            price: 59900,
            stock_quantity: 5,
            is_active: true,
            option_values: [{ id: 1, value: 'Small' }],
        },
        {
            id: 2,
            sku: 'MUG-L',
            price: 79900,
            stock_quantity: 5,
            is_active: true,
            option_values: [{ id: 2, value: 'Large' }],
        },
    ],
};

describe('QuickView', () => {
    let mockUseCart: any;

    beforeEach(() => {
        mockUseCart = {
            addItem: vi.fn(),
            lastAddedItem: ref(null),
        };
        (useCart as any).mockReturnValue(mockUseCart);

        // Mock Teleport/Dialog behavior if needed,
        // but since we are testing the component logic:
    });

    it('renders product details when open', () => {
        const wrapper = mount(QuickView, {
            props: {
                product: mockProduct as any,
                isOpen: true,
            },
            global: {
                stubs: {
                    Dialog: { template: '<div><slot /></div>' },
                    DialogContent: { template: '<div><slot /></div>' },
                    DialogOverlay: { template: '<div></div>' },
                },
            },
        });

        expect(wrapper.text()).toContain('Handmade Mug');
        expect(wrapper.text()).toContain('₱599.00');
        expect(wrapper.text()).toContain('A beautiful handmade mug');
    });

    it('handles variant selection', async () => {
        const wrapper = mount(QuickView, {
            props: {
                product: mockProduct as any,
                isOpen: true,
            },
            global: {
                stubs: {
                    Dialog: { template: '<div><slot /></div>' },
                    DialogContent: { template: '<div><slot /></div>' },
                    DialogOverlay: { template: '<div></div>' },
                },
            },
        });

        // Initially Small (first value)
        expect(wrapper.text()).toContain('₱599.00');

        // Click "Large" button
        const buttons = wrapper.findAll('button');
        const largeButton = buttons.find((b) => b.text() === 'Large');
        await largeButton?.trigger('click');

        expect(wrapper.text()).toContain('₱799.00');
    });

    it('calls addItem with correct variant data', async () => {
        const wrapper = mount(QuickView, {
            props: {
                product: mockProduct as any,
                isOpen: true,
            },
            global: {
                stubs: {
                    Dialog: { template: '<div><slot /></div>' },
                    DialogContent: { template: '<div><slot /></div>' },
                    DialogOverlay: { template: '<div></div>' },
                },
            },
        });

        const addToBagButton = wrapper
            .findAll('button')
            .find((b) => b.text().includes('Add to Bag'));
        await addToBagButton?.trigger('click');

        expect(mockUseCart.addItem).toHaveBeenCalledWith(
            expect.objectContaining({
                productId: 1,
                variantId: 1, // Default selected first variant
                price: 59900,
            }),
        );
    });

    it('emits close event', async () => {
        const wrapper = mount(QuickView, {
            props: {
                product: mockProduct as any,
                isOpen: true,
            },
            global: {
                stubs: {
                    Dialog: { template: '<div><slot /></div>' },
                    DialogContent: { template: '<div><slot /></div>' },
                    DialogOverlay: { template: '<div></div>' },
                },
            },
        });

        // The X button is the first button in the component
        const closeButton = wrapper.find('button');
        await closeButton.trigger('click');

        expect(wrapper.emitted()).toHaveProperty('close');
    });
});
