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
}

export interface StorefrontProduct {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price: number;
    compare_price: number | null;
    sku: string | null;
    stock_quantity: number;
    is_active: boolean;
    categories: StorefrontCategory[];
    images: StorefrontProductImage[];
    options?: StorefrontOption[];
    variants?: StorefrontVariant[];
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
    total: number;
    links: PaginationLink[];
}
