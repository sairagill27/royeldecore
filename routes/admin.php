<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\ProductController;
use Illuminate\Support\Facades\Route;

// Every route in this file is already protected by the login and "staff" guard
// (see routes/web.php). The route names start with "admin." automatically.
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Products: list, add, edit, delete
Route::resource('products', ProductController::class)->except('show');

// Categories: list, add, edit, delete
Route::resource('categories', CategoryController::class)->except('show');

// Orders: list, view one, change status. {order:order_number} finds the order by its number.
Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
Route::get('orders/{order:order_number}', [OrderController::class, 'show'])->name('orders.show');
Route::patch('orders/{order:order_number}/status', [OrderController::class, 'updateStatus'])
    ->middleware('throttle:30,1')
    ->name('orders.status');

// Customers: list, view one, block or unblock
Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
Route::get('customers/{user}', [CustomerController::class, 'show'])->name('customers.show');
Route::patch('customers/{user}/status', [CustomerController::class, 'toggle'])
    ->middleware('throttle:30,1')
    ->name('customers.toggle');

// Contact messages: inbox, read one, mark unread, delete
Route::get('messages', [MessageController::class, 'index'])->name('messages.index');
Route::get('messages/{message}', [MessageController::class, 'show'])->name('messages.show');
Route::patch('messages/{message}/unread', [MessageController::class, 'unread'])->name('messages.unread');
Route::delete('messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');

// Blog posts: list, write, edit, delete, plus the live Markdown preview
Route::post('posts/preview', [PostController::class, 'preview'])
    ->middleware('throttle:30,1')
    ->name('posts.preview');
Route::resource('posts', PostController::class)->except('show');
