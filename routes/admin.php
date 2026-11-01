<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CommentController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\PostImageController;
use App\Http\Controllers\Admin\PostPreviewLinkController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReviewController;
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

// Blog posts: SEO audit, the live preview, pictures for inside articles, then list, write, edit, delete
Route::get('posts/seo-audit', [PostController::class, 'audit'])->name('posts.audit');
Route::post('posts/preview', [PostController::class, 'preview'])
    ->middleware('throttle:30,1')
    ->name('posts.preview');
Route::post('posts/images', PostImageController::class)
    ->middleware('throttle:30,1')
    ->name('posts.images');
// A private, expiring link to see a post as visitors will (works for drafts too)
Route::get('posts/{post}/preview-link', PostPreviewLinkController::class)
    ->middleware('throttle:30,1')->name('posts.preview-link');
Route::resource('posts', PostController::class)->except('show');

// Blog comments: list, approve, reject, delete
Route::get('comments', [CommentController::class, 'index'])->name('comments.index');
Route::patch('comments/{comment}/approve', [CommentController::class, 'approve'])
    ->middleware('throttle:60,1')->name('comments.approve');
Route::patch('comments/{comment}/reject', [CommentController::class, 'reject'])
    ->middleware('throttle:60,1')->name('comments.reject');
Route::delete('comments/{comment}', [CommentController::class, 'destroy'])
    ->middleware('throttle:60,1')->name('comments.destroy');

// Product reviews: list, approve, reject, delete
Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');
Route::patch('reviews/{review}/approve', [ReviewController::class, 'approve'])
    ->middleware('throttle:60,1')->name('reviews.approve');
Route::patch('reviews/{review}/reject', [ReviewController::class, 'reject'])
    ->middleware('throttle:60,1')->name('reviews.reject');
Route::delete('reviews/{review}', [ReviewController::class, 'destroy'])
    ->middleware('throttle:60,1')->name('reviews.destroy');

// Notifications: everything waiting for you
Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
