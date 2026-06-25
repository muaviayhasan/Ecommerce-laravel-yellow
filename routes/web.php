<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Storefront\BlogController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\ShopController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront (website single theme)
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/shop', [ShopController::class, 'index'])->name('shop');
Route::get('/product/{slug}', [ProductController::class, 'show'])->name('product.show');

Route::get('/blog', [BlogController::class, 'index'])->name('blog');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');

// Authentication (login + register are functional)
Route::get('/login', [AuthController::class, 'create'])->name('login');
Route::post('/login', [AuthController::class, 'store']);
Route::get('/register', [RegisterController::class, 'create'])->name('register');
Route::post('/register', [RegisterController::class, 'store']);
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

// Placeholder routes — these pages are built in later modules. They keep the
// theme's navigation working (no 404s) and render a "coming soon" page.
$placeholders = [
    'cart' => 'Shopping Cart',
    'wishlist' => 'Wishlist',
    'compare' => 'Compare',
    'account' => 'My Account',
    'contact' => 'Contact Us',
];

foreach ($placeholders as $uri => $title) {
    Route::get("/{$uri}", fn () => app(HomeController::class)->placeholder($title))->name($uri);
}

Route::get('/track-order', fn () => app(HomeController::class)->placeholder('Track Your Order'))->name('track.order');
