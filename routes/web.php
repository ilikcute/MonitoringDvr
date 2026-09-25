<?php

use Illuminate\Support\Facades\Route;

// Named login route so any route('login') calls will resolve cleanly to the SPA view
Route::get('/login', function () {
    return view('app');
})->name('login');

// SPA fallback: seluruh navigasi ditangani oleh Vue Router
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api).*$');
