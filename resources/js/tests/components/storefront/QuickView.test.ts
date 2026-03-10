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
vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const actual = await importOriginal<any>();
    return {
        ...actual,
        Link: { template: '<a><slot /></a>' },
    };
});

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
    images: [{ id: 1, path: '/img.jpg', url: '/storage/img.jpg', alt_text: 'Mug', sort_order: 0 }],
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
            images: [],
        },
        {
            id: 2,
            sku: 'MUG-L',
            price: 79900,
            stock_quantity: 5,
            is_active: true,
            option_values: [{ id: 2, value: 'Large' }],
            images: [{ id: 10, path: '/large.jpg', url: '/storage/large.jpg', alt_text: 'Large', sort_order: 0 }],
        },
    ],
};

const globalStubs = {
    Dialog: { template: '<div><slot /></div>' },
    DialogContent: { template: '<div><slot /></div>' },
    DialogOverlay: { template: '<div></div>' },
    DialogTitle: { template: '<div><slot /></div>' },
    DialogDescription: { template: '<div><slot /></div>' },
};

describe('QuickView', () => {
    let mockUseCart: any;

    beforeEach(() => {
        mockUseCart = {
            addItem: vi.fn(),
            lastAddedItem: ref(null),
        };
        (useCart as any).mockReturnValue(mockUseCart);
    });

    it('renders product details when open', () => {
        const wrapper = mount(QuickView, {
            props: {
                product: mockProduct as any,
                isOpen: true,
            },
            global: {
                stubs: globalStubs,
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
                stubs: globalStubs,
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
                stubs: globalStubs,
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

    it('uses variant image when selected variant has images', async () => {
        const wrapper = mount(QuickView, {
            props: {
                product: mockProduct as any,
                isOpen: true,
            },
            global: {
                stubs: globalStubs,
            },
        });

        // Select "Large" (variant with an image)
        const largeButton = wrapper.findAll('button').find((b) => b.text() === 'Large');
        await largeButton?.trigger('click');

        const addToBagButton = wrapper.findAll('button').find((b) => b.text().includes('Add to Bag'));
        await addToBagButton?.trigger('click');

        expect(mockUseCart.addItem).toHaveBeenCalledWith(
            expect.objectContaining({
                image: '/storage/large.jpg',
            }),
        );
    });

    it('falls back to product image when selected variant has no images', async () => {
        const wrapper = mount(QuickView, {
            props: {
                product: mockProduct as any,
                isOpen: true,
            },
            global: {
                stubs: globalStubs,
            },
        });

        // "Small" is selected by default and has no images
        const addToBagButton = wrapper.findAll('button').find((b) => b.text().includes('Add to Bag'));
        await addToBagButton?.trigger('click');

        expect(mockUseCart.addItem).toHaveBeenCalledWith(
            expect.objectContaining({
                image: '/storage/img.jpg',
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
                stubs: globalStubs,
            },
        });

        // The X button is the first button in the component
        const closeButton = wrapper.find('button');
        await closeButton.trigger('click');

        expect(wrapper.emitted()).toHaveProperty('close');
    });
});
