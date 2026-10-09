<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use Illuminate\Support\Facades\Route;

// Every route in this file is already protected by the login and "staff" guard
// (see routes/web.php). The route names start with "admin." automatically.
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Products: list, add, edit, delete
Route::resource('products', ProductController::class)->except('show');

// Orders: list, view one, change status. {order:order_number} finds the order by its number.
Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
Route::get('orders/{order:order_number}', [OrderController::class, 'show'])->name('orders.show');
Route::patch('orders/{order:order_number}/status', [OrderController::class, 'updateStatus'])
    ->middleware('throttle:30,1')
    ->name('orders.status');
