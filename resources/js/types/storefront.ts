export interface StorefrontCategory {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    children: StorefrontCategory[];
}

export interface StorefrontProductImage {
    id: number;
    path: string;
    url: string;
    alt_text: string | null;
    sort_order: number;
}

export interface StorefrontOptionValue {
    id: number;
    value: string;
    position: number;
}

export interface StorefrontOption {
    id: number;
    name: string;
    position: number;
    values: StorefrontOptionValue[];
}

export interface StorefrontVariantOptionValue {
    id: number;
    value: string;
    option: { id: number; name: string };
}

export interface StorefrontVariant {
    id: number;
    sku: string | null;
    price: number | null;
    stock_quantity: number;
    is_active: boolean;
    option_values: StorefrontVariantOptionValue[];
    images?: StorefrontProductImage[];
}

export interface StorefrontRelatedProduct {
    id: number;
    name: string;
    slug: string;
    price: number;
    compare_price: number | null;
    images: StorefrontProductImage[];
}

export interface StorefrontBundleItem {
    id: number;
    quantity: number;
    component_product: {
        id: number;
        name: string;
        slug: string;
        images: StorefrontProductImage[];
    };
    component_variant: {
        id: number;
        option_values: StorefrontVariantOptionValue[];
    } | null;
}

export interface StorefrontProduct {
    id: number;
    type: string;
    name: string;
    slug: string;
    description: string | null;
    meta_title: string | null;
    meta_description: string | null;
    price: number;
    compare_price: number | null;
    sku: string | null;
    stock_quantity: number;
    is_active: boolean;
    categories: StorefrontCategory[];
    images: StorefrontProductImage[];
    options?: StorefrontOption[];
    variants?: StorefrontVariant[];
    related_products?: StorefrontRelatedProduct[];
    bundle_items?: StorefrontBundleItem[];
}

export interface CartItem {
    productId: number;
    variantId: number | null;
    name: string;
    slug: string;
    sku: string | null;
    price: number;
    quantity: number;
    image: string | null;
    variantLabel: string | null;
}

export interface ServerCartItem {
    id: number;
    product_id: number;
    variant_id: number | null;
    quantity: number;
    unit_price: number;
    subtotal: number;
}

export interface ServerCart {
    id: number;
    item_count: number;
    subtotal: number;
    items: ServerCartItem[];
}

export interface ShippingRate {
    shipping_method_id: number | null;
    carrier: string | null;
    service_code: string | null;
    name: string;
    description: string | null;
    amount_cents: number;
    type: 'flat_rate' | 'calculated';
    expected_delivery: string | null;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginatedProducts {
    data: StorefrontProduct[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: PaginationLink[];
}
