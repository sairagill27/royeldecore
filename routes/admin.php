<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use Illuminate\Support\Facades\Route;

// Every route in this file is already protected by the login and "staff" guard
// (see routes/web.php). The route names start with "admin." automatically.
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Products: list, add, edit, delete (one line creates all of them)
Route::resource('products', ProductController::class)->except('show');
