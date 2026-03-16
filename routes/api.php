<?php

use App\Http\Controllers\Api\V1\CartController as ApiCartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CouponController as ApiCouponController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');

    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('categories/{category}', [CategoryController::class, 'show'])->name('categories.show');

    Route::post('coupons/validate', [ApiCouponController::class, 'validate'])->name('coupons.validate');

    Route::prefix('cart')->name('cart.')->group(function () {
        Route::get('/', [ApiCartController::class, 'show'])->name('show');
        Route::post('/items', [ApiCartController::class, 'addItem'])->name('items.store');
        Route::patch('/items/{cartItem}', [ApiCartController::class, 'updateItem'])->name('items.update');
        Route::delete('/items/{cartItem}', [ApiCartController::class, 'removeItem'])->name('items.destroy');
        Route::delete('/', [ApiCartController::class, 'clear'])->name('clear');
    });
});
