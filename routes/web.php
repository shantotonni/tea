<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| The admin panel is a Vue 3 SPA. Every route below returns the same shell
| view; vue-router decides what to render. Keep the catch-all last.
|
*/

Route::get('/command', function () {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('config:cache');
    Artisan::call('optimize');
    return 'DONE';
});

Route::get('/', function () {
    return redirect('/login');
});

// SPA shell — /login and everything under /admin
Route::get('/login', function () {
    return view('admin');
});

Route::get('/admin/{any?}', function () {
    return view('admin');
})->where('any', '.*');
