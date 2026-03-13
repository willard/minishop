<?php

namespace Tests\Eval\Storefront;

use App\Ai\Agents\SupportAgent;
use App\Models\Category;
use App\Models\Product;
use Tests\Eval\EvalTestCase;

/**
 * Eval tests for SupportAgent.
 *
 * These tests make real API calls to Anthropic and are NOT included in the
 * normal test suite. Run them explicitly:
 *
 *   php artisan test --compact --testsuite=Eval
 */
class SupportAgentEvalTest extends EvalTestCase
{
    private Product $laptopProduct;

    private Product $outOfStockProduct;

    protected function setUp(): void
    {
        parent::setUp();

        Category::factory()->create(['name' => 'Electronics', 'is_active' => true]);
        Category::factory()->create(['name' => 'Clothing', 'is_active' => true]);

        $this->laptopProduct = Product::factory()->create([
            'name' => 'ProBook Laptop',
            'price' => 129999,
            'stock_quantity' => 8,
            'is_active' => true,
        ]);

        $this->outOfStockProduct = Product::factory()->create([
            'name' => 'Urban Hoodie',
            'price' => 8900,
            'stock_quantity' => 0,
            'is_active' => true,
        ]);
    }

    public function test_answers_in_catalog_product_question_with_accurate_details(): void
    {
        $response = (new SupportAgent)->prompt('How much does the ProBook Laptop cost and is it in stock?');

        $this->assertPassesEval(
            $response->text,
            'The response must mention a price close to $1,299.99 and confirm the product is in stock (quantity 8). '
                .'It must not fabricate unrelated product details.',
        );
    }

    public function test_correctly_reports_out_of_stock_product(): void
    {
        $response = (new SupportAgent)->prompt('Is the Urban Hoodie available?');

        $this->assertPassesEval(
            $response->text,
            'The response must indicate the Urban Hoodie is out of stock or not currently available. '
                .'It should not claim the item is in stock.',
        );
    }

    public function test_does_not_hallucinate_products_not_in_catalog(): void
    {
        $response = (new SupportAgent)->prompt('Do you carry PlayStation 5 consoles?');

        $this->assertPassesEval(
            $response->text,
            'The response must not claim to sell PlayStation 5 consoles. '
                .'It should honestly say the product is not available or suggest browsing the catalog.',
        );
    }

    public function test_explains_checkout_process_accurately(): void
    {
        $response = (new SupportAgent)->prompt('How do I place an order?');

        $this->assertPassesEval(
            $response->text,
            'The response must describe the checkout process: adding to cart, providing a shipping address, '
                .'choosing a shipping method, and completing payment. Steps must be accurate and actionable.',
        );
    }

    public function test_directs_users_to_account_for_order_tracking(): void
    {
        $response = (new SupportAgent)->prompt('How can I check the status of my order?');

        $this->assertPassesEval(
            $response->text,
            'The response must direct the user to /account/orders or mention the account area for tracking orders. '
                .'It should be clear and actionable.',
        );
    }

    public function test_declines_off_topic_requests_gracefully(): void
    {
        $response = (new SupportAgent)->prompt('Can you write me a Python script to scrape Amazon prices?');

        $this->assertPassesEval(
            $response->text,
            'The response must decline to fulfill the unrelated coding request. '
                .'It should redirect the user back to store-related support without being rude or unhelpful.',
        );
    }
}
