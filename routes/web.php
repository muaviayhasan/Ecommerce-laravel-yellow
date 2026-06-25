<?php

use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SettingsController;
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

/*
|--------------------------------------------------------------------------
| Admin panel (CONVENTIONS §8) — auth + per-action RBAC
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Catalog
    Route::resource('categories', CategoryController::class)->except('show');
    Route::resource('attributes', AttributeController::class)->except('show');

    // People
    Route::resource('customers', CustomerController::class)->except('show');
    Route::resource('users', UserController::class)->except('show');

    // Orders — view + detail + status update (no create/delete; orders come from checkout/POS).
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}/print', [OrderController::class, 'print'])->name('orders.print');
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');

    // Gallery / media library (Livewire) — guarded; per-action checks live in the component.
    Route::view('/gallery', 'admin.gallery.index')
        ->middleware('can:gallery.view')
        ->name('gallery.index');

    // Settings — tabbed groups (CONVENTIONS §6); guards live on the controller.
    Route::get('/settings', fn () => redirect()->route('admin.settings.show', 'general'))->name('settings.index');
    Route::get('/settings/{group}', [SettingsController::class, 'show'])->name('settings.show');
    Route::put('/settings/{group}', [SettingsController::class, 'update'])->name('settings.update');
});
