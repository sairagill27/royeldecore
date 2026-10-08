<?php

use Illuminate\Support\Facades\Route;

// Every route in this file is already protected by the login and "staff" guard
// (see routes/web.php). The real dashboard replaces this test line in the next step.
Route::get('/', fn() => 'Admin area works.')->name('dashboard');
