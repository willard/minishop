<?php

use App\Http\Controllers\Account\AddressController;
use App\Http\Controllers\Account\DashboardController as AccountDashboardController;
use App\Http\Controllers\Account\OrdersController as AccountOrdersController;
use App\Http\Controllers\Account\PaymentController as AccountPaymentController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\ProductOptionController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\ReturnController;
use App\Http\Controllers\Admin\ShippingMethodController;
use App\Http\Controllers\Admin\StoreSettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\PaymentController;
use App\Http\Controllers\Storefront\ProductController as StorefrontProductController;
use App\Http\Controllers\Storefront\SupportChatController;
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
    ->middleware(['auth', 'verified', 'role:super-admin|admin|manager'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'role:super-admin|admin|manager'])->prefix('dashboard')->name('admin.')->group(function () {
    Route::get('products/export', [ProductController::class, 'export'])->name('products.export');
    Route::resource('products', ProductController::class);
    Route::resource('products.variants', ProductVariantController::class)->except(['index', 'show'])->scoped();
    Route::resource('products.options', ProductOptionController::class)->only(['create', 'store', 'destroy'])->scoped();
    Route::post('products/{product}/images', [ProductImageController::class, 'store'])->name('products.images.store');
    Route::put('products/{product}/images/reorder', [ProductImageController::class, 'reorder'])->name('products.images.reorder');
    Route::delete('products/{product}/images/{image}', [ProductImageController::class, 'destroy'])->name('products.images.destroy');
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('orders', OrderController::class)->except(['edit']);
    Route::get('orders/{order}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice');
    Route::resource('returns', ReturnController::class)->only(['index', 'create', 'store', 'show', 'update']);
    Route::post('returns/{return}/approve', [ReturnController::class, 'approve'])->name('returns.approve');
    Route::post('returns/{return}/reject', [ReturnController::class, 'reject'])->name('returns.reject');
    Route::post('returns/{return}/receive', [ReturnController::class, 'receive'])->name('returns.receive');
    Route::post('returns/{return}/refund', [ReturnController::class, 'refund'])->name('returns.refund');
    Route::resource('customers', CustomerController::class)->only(['index', 'show']);
    Route::resource('users', UserController::class)->except(['show']);
    Route::resource('coupons', CouponController::class)->except(['show']);
    Route::resource('shipping-methods', ShippingMethodController::class)->except(['show']);
    Route::get('settings', [StoreSettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [StoreSettingsController::class, 'update'])->name('settings.update');
    Route::get('activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');
});

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
