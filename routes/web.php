<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductOptionController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\ShippingMethodController;
use App\Http\Controllers\Admin\StoreSettingsController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\PaymentController;
use App\Http\Controllers\Storefront\ProductController as StorefrontProductController;
use App\Http\Controllers\Webhooks\PayMongoWebhookController;
use App\Http\Controllers\Webhooks\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::prefix('products')->name('storefront.products.')->group(function () {
    Route::get('/', [StorefrontProductController::class, 'index'])->name('index');
    Route::get('/{product:slug}', [StorefrontProductController::class, 'show'])->name('show');
});

Route::prefix('checkout')->name('storefront.checkout.')->group(function () {
    Route::get('/', [CheckoutController::class, 'create'])->name('create');
    Route::post('/', [CheckoutController::class, 'store'])->name('store');
    Route::get('/pay/{order:order_number}', [PaymentController::class, 'show'])->name('payment.show');
    Route::post('/pay/{order:order_number}/stripe', [PaymentController::class, 'stripeIntent'])->name('payment.stripe');
    Route::post('/pay/{order:order_number}/paymongo', [PaymentController::class, 'paymongoCheckout'])->name('payment.paymongo');
    Route::get('/pay/{order:order_number}/callback', [PaymentController::class, 'paymongoCallback'])->name('payment.callback');
});

Route::post('/webhooks/stripe', StripeWebhookController::class)->name('webhooks.stripe');
Route::post('/webhooks/paymongo', PayMongoWebhookController::class)->name('webhooks.paymongo');

Route::get('/order-confirmation/{order}', [CheckoutController::class, 'confirmation'])
    ->name('storefront.order.confirmation');

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->prefix('dashboard')->name('admin.')->group(function () {
    Route::resource('products', ProductController::class);
    Route::resource('products.variants', ProductVariantController::class)->except(['index', 'show'])->scoped();
    Route::resource('products.options', ProductOptionController::class)->only(['create', 'store', 'destroy'])->scoped();
    Route::resource('categories', CategoryController::class);
    Route::resource('orders', OrderController::class)->except(['create', 'store', 'edit']);
    Route::resource('customers', CustomerController::class)->only(['index', 'show']);
    Route::resource('coupons', CouponController::class)->except(['show']);
    Route::resource('shipping-methods', ShippingMethodController::class)->except(['show']);
    Route::get('settings', [StoreSettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [StoreSettingsController::class, 'update'])->name('settings.update');
});

require __DIR__.'/settings.php';
