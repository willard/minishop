import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';
import { useCart } from '@/composables/useCart';
import Home from '@/pages/storefront/Home.vue';

// Mock useCart
vi.mock('@/composables/useCart', () => ({
    useCart: vi.fn(),
}));

// Mock Inertia components
vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const actual = await importOriginal<any>();
    return {
        ...actual,
        Head: { template: '<div><slot /></div>' },
        Link: { template: '<a><slot /></a>' },
    };
});

const mockProducts = [
    {
        id: 1,
        name: 'Handmade Mug',
        slug: 'handmade-mug',
        sku: 'MUG-001',
        price: 59900,
        stock_quantity: 10,
        images: [],
        categories: [],
    },
];

describe('Home Page', () => {
    let mockUseCart: any;

    beforeEach(() => {
        mockUseCart = {
            addItem: vi.fn(),
            lastAddedItem: ref(null),
            isDrawerOpen: ref(false),
            itemCount: ref(0),
            subtotal: ref(0),
            cartItems: ref([]),
            openDrawer: vi.fn(),
            closeDrawer: vi.fn(),
            removeItem: vi.fn(),
            updateQuantity: vi.fn(),
            clearCart: vi.fn(),
        };
        (useCart as any).mockReturnValue(mockUseCart);
    });

    it('renders featured products', () => {
        const wrapper = mount(Home, {
            props: {
                featuredProducts: mockProducts,
                categories: [],
            },
        });
        expect(wrapper.text()).toContain('Handmade Mug');
    });

    it('calls addItem when Add button is clicked', async () => {
        const wrapper = mount(Home, {
            props: {
                featuredProducts: mockProducts,
                categories: [],
            },
        });

        // Target the "Add" button specifically - it has 'Add' text
        const buttons = wrapper.findAll('button');
        const addButton = buttons.find((b) => b.text() === 'Add');

        expect(addButton).toBeDefined();
        await addButton?.trigger('click');

        expect(mockUseCart.addItem).toHaveBeenCalled();
    });

    it('shows "Added!" feedback when product is the last added item', () => {
        mockUseCart.lastAddedItem.value = { productId: 1 };

        const wrapper = mount(Home, {
            props: {
                featuredProducts: mockProducts,
                categories: [],
            },
        });

        const buttons = wrapper.findAll('button');
        const addButton = buttons.find((b) => b.text() === 'Added!');
        expect(addButton).toBeDefined();
    });

    it('opens Quick View when Quick View button is clicked', async () => {
        const wrapper = mount(Home, {
            props: {
                featuredProducts: mockProducts,
                categories: [],
            },
        });

        const buttons = wrapper.findAll('button');
        const quickViewButton = buttons.find((b) =>
            b.text().includes('Quick View'),
        );

        expect(quickViewButton).toBeDefined();
        await quickViewButton?.trigger('click');

        const quickView = wrapper.findComponent({ name: 'QuickView' });
        expect(quickView.props('isOpen')).toBe(true);
        expect(quickView.props('product')).toEqual(mockProducts[0]);
    });
});
