<?php

use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\BomController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\LedgerController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PosController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProductionController;
use App\Http\Controllers\Admin\PurchaseController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\SupplierController;
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

    // Heartbeat — keeps the session alive and hands back a fresh CSRF token so
    // long-open admin forms don't fail with a 419 (see layouts/admin.blade.php).
    Route::get('keep-alive', fn () => response()->json(['token' => csrf_token()]))->name('keep-alive');

    // Catalog
    Route::resource('products', AdminProductController::class);
    Route::resource('categories', CategoryController::class)->except('show');
    Route::resource('brands', BrandController::class)->except('show');
    Route::resource('attributes', AttributeController::class)->except('show');

    // People
    Route::resource('customers', CustomerController::class)->except('show');
    Route::resource('users', UserController::class)->except('show');

    // POS — fast counter-sale screen (Alpine cart + JSON search + SalesService).
    Route::get('pos', [PosController::class, 'index'])->name('pos.index');
    Route::get('pos/search', [PosController::class, 'search'])->name('pos.search');
    Route::post('pos', [PosController::class, 'store'])->name('pos.store');

    // Orders — view + detail + status update (no create/delete; orders come from checkout/POS).
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}/print', [OrderController::class, 'print'])->name('orders.print');
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');

    // Procurement — suppliers + purchasing (receive posts stock + moving-avg cost + ledger).
    Route::resource('suppliers', SupplierController::class)->except('show');
    Route::post('purchases/{purchase}/receive', [PurchaseController::class, 'receive'])->name('purchases.receive');
    Route::post('purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])->name('purchases.cancel');
    Route::resource('purchases', PurchaseController::class);

    // Manufacturing — BOMs (recipes) + production runs (complete consumes/produces + ledger).
    Route::resource('boms', BomController::class);
    Route::post('production/{order}/complete', [ProductionController::class, 'complete'])->name('production.complete');
    Route::post('production/{order}/cancel', [ProductionController::class, 'cancel'])->name('production.cancel');
    Route::resource('production', ProductionController::class)->parameters(['production' => 'order']);

    // Inventory — stock levels + manual adjustments (StockService + ledger) + movement history.
    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('inventory/{variant}/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
    Route::get('inventory/{variant}', [InventoryController::class, 'show'])->name('inventory.show');

    // Reports — analytics dashboard + CSV export.
    Route::get('reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [ReportsController::class, 'export'])->name('reports.export');

    // Ledger — the financial source of truth (read-only): position, trial balance, entries.
    Route::get('ledger', [LedgerController::class, 'index'])->name('ledger.index');

    // Gallery / media library (Livewire) — guarded; per-action checks live in the component.
    Route::view('/gallery', 'admin.gallery.index')
        ->middleware('can:gallery.view')
        ->name('gallery.index');

    // Settings — tabbed groups (CONVENTIONS §6); guards live on the controller.
    Route::get('/settings', fn () => redirect()->route('admin.settings.show', 'general'))->name('settings.index');
    Route::get('/settings/{group}', [SettingsController::class, 'show'])->name('settings.show');
    Route::put('/settings/{group}', [SettingsController::class, 'update'])->name('settings.update');
});
