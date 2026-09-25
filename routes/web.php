<?php

use Illuminate\Support\Facades\Route;

// SPA fallback: seluruh navigasi ditangani oleh Vue Router
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api).*$');
