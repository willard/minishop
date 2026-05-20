<?php

use App\Http\Controllers\Account\AddressController;
use App\Http\Controllers\Account\DashboardController as AccountDashboardController;
use App\Http\Controllers\Account\OrdersController as AccountOrdersController;
use App\Http\Controllers\Account\PaymentController as AccountPaymentController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\CheckoutShippingRatesController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\PaymentController;
use App\Http\Controllers\Storefront\ProductController as StorefrontProductController;
use App\Http\Controllers\Storefront\SupportChatController;
use App\Http\Controllers\Storefront\TaxPreviewController;
use App\Http\Controllers\Webhooks\PayMongoWebhookController;
use App\Http\Controllers\Webhooks\StripeWebhookController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', HomeController::class)->name('home');

Route::post('/chat', [SupportChatController::class, 'store'])->name('storefront.chat.store');

Route::prefix('products')->name('storefront.products.')->group(function () {
    Route::get('/', [StorefrontProductController::class, 'index'])->name('index');
    Route::get('/{product:slug}', [StorefrontProductController::class, 'show'])->name('show');
});

Route::prefix('cart')->name('storefront.cart.')->group(function () {
    Route::get('/', [CartController::class, 'show'])->name('show');
    Route::post('/items', [CartController::class, 'addItem'])->name('items.store');
    Route::patch('/items/{cartItem}', [CartController::class, 'updateItem'])->name('items.update');
    Route::delete('/items/{cartItem}', [CartController::class, 'removeItem'])->name('items.destroy');
    Route::delete('/', [CartController::class, 'clear'])->name('clear');
    Route::post('/sync', [CartController::class, 'sync'])->name('sync');
});

Route::prefix('checkout')->name('storefront.checkout.')->group(function () {
    Route::get('/', [CheckoutController::class, 'create'])->name('create');
    Route::post('/shipping-rates', CheckoutShippingRatesController::class)
        ->middleware('throttle:20,1')
        ->name('shipping-rates');
    Route::post('/tax-preview', TaxPreviewController::class)
        ->middleware('throttle:30,1')
        ->name('tax-preview');
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

// Storefront registration page (separate from admin /register)
Route::get('/register/customer', fn () => Inertia::render('storefront/auth/Register'))
    ->middleware('guest')
    ->name('storefront.register');

// Customer account area
Route::middleware(['auth', 'verified', 'role:customer'])->prefix('account')->name('account.')->group(function () {
    Route::get('/', AccountDashboardController::class)->name('dashboard');
    Route::get('/orders', [AccountOrdersController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AccountOrdersController::class, 'show'])->name('orders.show');
    Route::get('/address', [AddressController::class, 'edit'])->name('address.edit');
    Route::put('/address', [AddressController::class, 'update'])->name('address.update');
    Route::get('/payment', [AccountPaymentController::class, 'index'])->name('payment.index');
});

require __DIR__.'/settings.php';
