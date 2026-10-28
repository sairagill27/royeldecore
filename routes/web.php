<?php

use App\Http\Controllers\Account\OrderController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\ToolController;
use Illuminate\Support\Facades\Route;


Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/shop/{category:slug?}', [ShopController::class, 'index'])->name('shop');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');

// SEO FILES for search engines
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
    Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
});

// SEARCH (the box in the header sends ?q=word to this address)
Route::get('/search', [SearchController::class, 'index'])->middleware('throttle:60,1')->name('search');

// BLOG. The fixed addresses (category, tag, feed) come BEFORE /blog/{slug}, so they are matched first.
Route::middleware('throttle:120,1')->group(function () {
    Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
    Route::get('/blog/feed.xml', [SeoController::class, 'feed'])->name('blog.feed');
    Route::get('/blog/category/{category:slug}', [BlogController::class, 'category'])->name('blog.category');
    Route::get('/blog/tag/{tag:slug}', [BlogController::class, 'tag'])->name('blog.tag');
    Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
});

// COMMENTS. throttle:5,10 = max 5 comments per 10 minutes per visitor (stops spam)
Route::post('/blog/{slug}/comments', [CommentController::class, 'store'])
    ->middleware('throttle:5,10')->name('blog.comments.store');

// DECOR TOOLS: the calculators. The sums are done on the server, so the page can show a result live.
Route::get('/tools', [ToolController::class, 'index'])->name('tools.index');
Route::get('/tools/{tool}', [ToolController::class, 'show'])
    ->where('tool', '[a-z0-9-]+')->name('tools.show');
Route::post('/tools/{tool}', [ToolController::class, 'calculate'])
    ->where('tool', '[a-z0-9-]+')->middleware('throttle:90,1')->name('tools.calculate');
// NEWSLETTER. throttle:5,10 = max 5 sign-ups per 10 minutes per visitor (stops spam)
Route::post('/newsletter', [NewsletterController::class, 'subscribe'])->middleware('throttle:5,10')->name('newsletter.subscribe');
Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])
    ->middleware('throttle:20,1')->name('newsletter.unsubscribe');


// ---------- INFORMATION PAGES ----------
Route::view('/about', 'pages.about')->name('about');

// Legal pages (the footer links to these addresses)
Route::view('/privacy-policy', 'pages.legal.privacy')->name('legal.privacy');
Route::view('/terms', 'pages.legal.terms')->name('legal.terms');
Route::view('/disclaimer', 'pages.legal.disclaimer')->name('legal.disclaimer');
Route::view('/affiliate-disclosure', 'pages.legal.affiliate')->name('legal.affiliate');
Route::view('/cookies', 'pages.legal.cookies')->name('legal.cookies');


// CONTACT. throttle:5,10 = max 5 messages per 10 minutes per visitor (stops spam)
Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,10')->name('contact.store');


// CART. throttle:60,1 = max 60 requests per minute per visitor (stops abuse)
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');

Route::middleware('throttle:60,1')->group(function () {
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart/{product}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{product}', [CartController::class, 'remove'])->name('cart.remove');
    Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');
});


// ---------- AUTH ----------
// "guest" = only visitors who are NOT logged in can open these
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    // PASSWORD RESET. The route name "password.reset" is required by Laravel's reset email.
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])
        ->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:6,1')->name('password.store');
});

// "auth" = only logged-in users. Visitors are sent to the login page.
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::view('/account', 'account.index')->name('account');

    // PROFILE and PASSWORD
    Route::get('/account/profile', [ProfileController::class, 'edit'])->name('account.profile');
    Route::patch('/account/profile', [ProfileController::class, 'update'])
        ->middleware('throttle:10,1')->name('account.profile.update');
    Route::put('/account/password', [ProfileController::class, 'password'])
        ->middleware('throttle:6,1')->name('account.password');

    // CHECKOUT. throttle: max 10 order attempts per minute.
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');

    // MY ORDERS
    Route::get('/account/orders', [OrderController::class, 'index'])->name('account.orders');
    Route::get('/account/orders/{order:order_number}', [OrderController::class, 'show'])->name('account.orders.show');
});


// ---------- ADMIN ----------
// auth = must be logged in. staff = must be an active admin or staff member.
Route::middleware(['auth', 'staff'])
    ->prefix('admin')
    ->name('admin.')
    ->group(base_path('routes/admin.php'));


// ---------- DEV ONLY: previews that exist only when APP_ENV=local, for staff ----------
if (app()->environment('local')) {
    Route::middleware(['auth', 'staff'])->group(function () {
        // Preview the order emails in the browser
        Route::get('/dev/mail/customer/{order:order_number}', fn(\App\Models\Order $order) => new \App\Mail\OrderPlaced($order->load('items')));
        Route::get('/dev/mail/admin/{order:order_number}', fn(\App\Models\Order $order) => new \App\Mail\NewOrderAlert($order->load('items')));

        // Preview an error page, for example /dev/error/404
        Route::get('/dev/error/{code}', fn(int $code) => abort($code))->whereIn('code', [403, 404, 419, 429, 500, 503]);
    });
}