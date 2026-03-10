import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';
import { useCart } from '@/composables/useCart';
import CheckoutPage from '@/pages/storefront/Checkout.vue';

const { mockUsePage } = vi.hoisted(() => ({
    mockUsePage: vi.fn(() => ({
        props: {
            auth: null as { user: { name: string; email: string } } | null,
            storeSettings: {
                currency: 'PHP',
                currencyLocale: 'en-PH',
                taxRate: 12,
            },
            shippingMethods: [],
        },
    })),
}));

vi.mock('@/composables/useCart', () => ({
    useCart: vi.fn(),
}));

vi.mock('@/composables/usePrice', () => ({
    usePrice: vi.fn(() => ({
        formatPrice: (amount: number) => `₱${amount}`,
    })),
}));

vi.mock('@inertiajs/vue3', () => ({
    Head: { name: 'Head', template: '<div />', props: ['title'] },
    Link: { name: 'Link', template: '<a href="#"><slot /></a>', props: ['href'] },
    Form: {
        name: 'Form',
        template: '<form><slot :errors="{}" :processing="false" /></form>',
        props: ['action', 'method', 'resetOnSuccess'],
    },
    usePage: mockUsePage,
    useForm: vi.fn(() => ({
        name: '',
        email: '',
        phone: '',
        address_line1: '',
        address_line2: '',
        city: '',
        state: '',
        postcode: '',
        country: 'PH',
        shipping_method_id: null,
        coupon_code: '',
        notes: '',
        items: [],
        post: vi.fn(),
        errors: {},
        processing: false,
    })),
}));

vi.mock('@/layouts/StorefrontLayout.vue', () => ({
    default: { name: 'StorefrontLayout', template: '<div><slot /></div>' },
}));

vi.mock('@/components/InputError.vue', () => ({
    default: { name: 'InputError', template: '<span />', props: ['message'] },
}));

vi.mock('@/routes/login', () => ({
    store: { form: vi.fn(() => ({ action: '/login', method: 'post' })) },
}));

vi.mock('@/routes/register', () => ({
    store: { form: vi.fn(() => ({ action: '/register', method: 'post' })) },
}));

vi.mock(
    '@/actions/App/Http/Controllers/Storefront/CheckoutController',
    () => ({
        store: vi.fn(() => ({ url: '/checkout/store', method: 'post' })),
    }),
);

vi.mock('@/actions/App/Http/Controllers/Storefront/ProductController', () => ({
    index: vi.fn(() => ({ url: '/products' })),
}));

vi.mock('lucide-vue-next', () => ({
    ShoppingBag: { template: '<svg />' },
    Trash2: { template: '<svg />' },
    Tag: { template: '<svg />' },
    ChevronDown: { template: '<svg />' },
    ChevronUp: { template: '<svg />' },
    Truck: { template: '<svg />' },
    User: { template: '<svg />' },
    LogIn: { template: '<svg />' },
    UserPlus: { template: '<svg />' },
    CheckCircle2: { template: '<svg />' },
    X: { template: '<svg />' },
}));

const mockCartItem = {
    productId: 1,
    variantId: null,
    name: 'Test Product',
    price: 59900,
    quantity: 1,
    image: null,
    slug: 'test-product',
    variantLabel: null,
};

describe('storefront/Checkout - auth section', () => {
    let mockUseCart: any;

    beforeEach(() => {
        mockUseCart = {
            cartItems: ref([mockCartItem]),
            itemCount: ref(1),
            subtotal: ref(59900),
            removeItem: vi.fn(),
            updateQuantity: vi.fn(),
            clearCart: vi.fn(),
            addItem: vi.fn(),
        };
        (useCart as any).mockReturnValue(mockUseCart);

        // Default: guest (no authenticated user)
        mockUsePage.mockReturnValue({
            props: {
                auth: null,
                storeSettings: {
                    currency: 'PHP',
                    currencyLocale: 'en-PH',
                    taxRate: 12,
                },
                shippingMethods: [],
            },
        });
    });

    describe('guest user', () => {
        it('shows the collapsed auth prompt when not signed in', () => {
            const wrapper = mount(CheckoutPage);

            expect(wrapper.text()).toContain('Have an account?');
        });

        it('shows Sign in and Register buttons in collapsed state', () => {
            const wrapper = mount(CheckoutPage);

            expect(wrapper.text()).toContain('Sign in');
            expect(wrapper.text()).toContain('Register');
        });

        it('opens the login form when Sign in is clicked', async () => {
            const wrapper = mount(CheckoutPage);

            const signInButton = wrapper
                .findAll('button')
                .find((b) => b.text().trim() === 'Sign in');
            await signInButton?.trigger('click');

            expect(wrapper.find('input[type="email"]').exists()).toBe(true);
            expect(wrapper.find('input[type="password"]').exists()).toBe(true);
        });

        it('opens the register form when Register is clicked', async () => {
            const wrapper = mount(CheckoutPage);

            const registerButton = wrapper
                .findAll('button')
                .find((b) => b.text().trim() === 'Register');
            await registerButton?.trigger('click');

            expect(wrapper.find('input[name="name"]').exists()).toBe(true);
            expect(wrapper.find('input[name="email"]').exists()).toBe(true);
        });

        it('includes a hidden redirect input pointing to /checkout in the login form', async () => {
            const wrapper = mount(CheckoutPage);

            const signInButton = wrapper
                .findAll('button')
                .find((b) => b.text().trim() === 'Sign in');
            await signInButton?.trigger('click');

            const redirectInput = wrapper.find('input[name="redirect"]');
            expect(redirectInput.exists()).toBe(true);
            expect(redirectInput.attributes('value')).toBe('/checkout');
        });

        it('includes a hidden redirect input pointing to /checkout in the register form', async () => {
            const wrapper = mount(CheckoutPage);

            const registerButton = wrapper
                .findAll('button')
                .find((b) => b.text().trim() === 'Register');
            await registerButton?.trigger('click');

            const redirectInput = wrapper.find('input[name="redirect"]');
            expect(redirectInput.exists()).toBe(true);
            expect(redirectInput.attributes('value')).toBe('/checkout');
        });

        it('collapses the auth panel when the close button is clicked', async () => {
            const wrapper = mount(CheckoutPage);

            const signInButton = wrapper
                .findAll('button')
                .find((b) => b.text().trim() === 'Sign in');
            await signInButton?.trigger('click');

            // Click the close button directly via the panel header
            const allButtons = wrapper.findAll('button');
            // The X button is the one right after the tab buttons in the panel header
            const xButton = allButtons.find(
                (b, i) =>
                    b.find('svg').exists() &&
                    !b.text().includes('Sign') &&
                    !b.text().includes('Create') &&
                    !b.text().includes('Register') &&
                    i > 0,
            );
            await xButton?.trigger('click');

            expect(wrapper.text()).toContain('Have an account?');
        });

        it('switches to the register tab when Create Account is clicked', async () => {
            const wrapper = mount(CheckoutPage);

            // Open login form first
            const signInButton = wrapper
                .findAll('button')
                .find((b) => b.text().trim() === 'Sign in');
            await signInButton?.trigger('click');

            // Switch to register tab
            const createAccountTab = wrapper
                .findAll('button')
                .find((b) => b.text().includes('Create Account'));
            await createAccountTab?.trigger('click');

            expect(wrapper.find('input[name="name"]').exists()).toBe(true);
        });
    });

    describe('authenticated user', () => {
        beforeEach(() => {
            mockUsePage.mockReturnValue({
                props: {
                    auth: {
                        user: { name: 'Jane Doe', email: 'jane@example.com' },
                    },
                    storeSettings: {
                        currency: 'PHP',
                        currencyLocale: 'en-PH',
                        taxRate: 12,
                    },
                    shippingMethods: [],
                },
            });
        });

        it('shows the signed-in indicator with user name and email', () => {
            const wrapper = mount(CheckoutPage);

            expect(wrapper.text()).toContain('Jane Doe');
            expect(wrapper.text()).toContain('jane@example.com');
        });

        it('does not show the guest auth prompt', () => {
            const wrapper = mount(CheckoutPage);

            expect(wrapper.text()).not.toContain('Have an account?');
        });
    });

    describe('empty cart', () => {
        it('shows empty cart message and hides the auth section', () => {
            mockUseCart.cartItems = ref([]);
            mockUseCart.itemCount = ref(0);
            (useCart as any).mockReturnValue(mockUseCart);

            const wrapper = mount(CheckoutPage);

            expect(wrapper.text()).toContain('Your cart is empty');
            expect(wrapper.text()).not.toContain('Have an account?');
        });
    });
});
