<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;



Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/shop/{category:slug?}', [ShopController::class, 'index'])->name('shop');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');

// CART. throttle:60,1 = max 60 requests per minute per visitor (stops abuse)
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');

Route::middleware('throttle:60,1')->group(function () {
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart/{product}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{product}', [CartController::class, 'remove'])->name('cart.remove');
    Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');
});
