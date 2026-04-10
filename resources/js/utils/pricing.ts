/**
 * Returns the effective price for a product, applying the sitewide sale discount
 * when the product is marked on_sale and a positive discount percentage is set.
 *
 * @param price - Base price in cents
 * @param onSale - Whether the product is marked as on sale
 * @param saleDiscountPercentage - Sitewide discount percentage (0–100)
 * @returns Effective price in cents
 */
export function effectivePrice(
    price: number,
    onSale: boolean,
    saleDiscountPercentage: number,
): number {
    if (onSale && saleDiscountPercentage > 0) {
        return Math.round(price * (1 - saleDiscountPercentage / 100));
    }
    return price;
}
