<?php

use App\Http\Controllers\Storefront\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront (website single theme)
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

// Placeholder routes — these pages are built in later modules. They keep the
// theme's navigation working (no 404s) and render a "coming soon" page.
$placeholders = [
    'shop' => 'Shop',
    'cart' => 'Shopping Cart',
    'wishlist' => 'Wishlist',
    'compare' => 'Compare',
    'account' => 'My Account',
    'blog' => 'Blog',
    'contact' => 'Contact Us',
    'login' => 'Sign In',
    'register' => 'Create Account',
];

foreach ($placeholders as $uri => $title) {
    Route::get("/{$uri}", fn () => app(HomeController::class)->placeholder($title))->name($uri);
}

Route::get('/track-order', fn () => app(HomeController::class)->placeholder('Track Your Order'))->name('track.order');
