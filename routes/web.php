<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

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
    $lock = storage_path('app/.command-used');
    $handle = @fopen($lock, 'x');
    abort_if($handle === false, 404);
    fclose($handle);

    try {
        Artisan::call('optimize:clear');
        Artisan::call('optimize');
    } catch (\Throwable $exception) {
        @unlink($lock);
        throw $exception;
    }

    return response()->json([
        'ok' => true,
        'message' => 'Laravel caches cleared and rebuilt. This one-time URL is now locked.',
    ]);
})->middleware('throttle:10,1');

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
