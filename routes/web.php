<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\BlogCategoryController;
use App\Http\Controllers\Admin\BlogPostController;
use App\Http\Controllers\Admin\BlogTagController;
use App\Http\Controllers\Admin\BomController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\LedgerController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PosController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProductionController;
use App\Http\Controllers\Admin\PurchaseController;
use App\Http\Controllers\Admin\QuotationController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\SupportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VendorSaleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Support\SocialLogin;
use App\Http\Controllers\Storefront\BlogController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\CompareController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\ReviewController as StorefrontReviewController;
use App\Http\Controllers\Storefront\ShopController;
use App\Http\Controllers\Storefront\SupportChatController;
use App\Http\Controllers\Storefront\WishlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront (website single theme)
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/shop', [ShopController::class, 'index'])->name('shop');
Route::get('/product/{slug}', [ProductController::class, 'show'])->name('product.show');
Route::post('/product/{product:slug}/reviews', [StorefrontReviewController::class, 'store'])->middleware('auth')->name('product.reviews.store');

Route::get('/blog', [BlogController::class, 'index'])->name('blog');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

// Cart (session-based)
Route::get('/cart', [CartController::class, 'index'])->name('cart');
Route::post('/cart', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/{variant}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{variant}', [CartController::class, 'remove'])->name('cart.remove');
Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');

// Wishlist + Compare (session-based)
Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist');
Route::post('/wishlist/{product:slug}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
Route::delete('/wishlist/{product:slug}', [WishlistController::class, 'remove'])->name('wishlist.remove');
Route::delete('/wishlist', [WishlistController::class, 'clear'])->name('wishlist.clear');

Route::get('/compare', [CompareController::class, 'index'])->name('compare');
Route::post('/compare/{product:slug}', [CompareController::class, 'toggle'])->name('compare.toggle');
Route::delete('/compare/{product:slug}', [CompareController::class, 'remove'])->name('compare.remove');
Route::delete('/compare', [CompareController::class, 'clear'])->name('compare.clear');

Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');

// Authentication (login + register are functional)
Route::get('/login', [AuthController::class, 'create'])->name('login');
Route::post('/login', [AuthController::class, 'store']);
Route::get('/register', [RegisterController::class, 'create'])->name('register');
Route::post('/register', [RegisterController::class, 'store']);
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

// Storefront social sign-in (customers) — Google/Facebook enabled from admin settings.
Route::get('/auth/{provider}', [SocialAuthController::class, 'redirect'])
    ->whereIn('provider', SocialLogin::PROVIDERS)->name('social.redirect');
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->whereIn('provider', SocialLogin::PROVIDERS)->name('social.callback');

// Admin authentication — staff-only sign-in, separate from the storefront customer login.
Route::get('/admin/login', [AdminAuthController::class, 'create'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'store'])->name('admin.login.store');
Route::post('/admin/logout', [AdminAuthController::class, 'destroy'])->middleware('auth')->name('admin.logout');
// Social SSO for staff — providers are enabled/configured from the admin "Social login" settings.
Route::get('/admin/auth/{provider}', [AdminAuthController::class, 'redirect'])
    ->whereIn('provider', SocialLogin::PROVIDERS)->name('admin.auth.redirect');
Route::get('/admin/auth/{provider}/callback', [AdminAuthController::class, 'callback'])
    ->whereIn('provider', SocialLogin::PROVIDERS)->name('admin.auth.callback');

// Placeholder routes — these pages are built in later modules. They keep the
// theme's navigation working (no 404s) and render a "coming soon" page.
$placeholders = [
    'account' => 'My Account',
    'contact' => 'Contact Us',
];

foreach ($placeholders as $uri => $title) {
    Route::get("/{$uri}", fn () => app(HomeController::class)->placeholder($title))->name($uri);
}

Route::get('/track-order', fn () => app(HomeController::class)->placeholder('Track Your Order'))->name('track.order');

// Support chat widget (public — works for guests and logged-in customers).
Route::get('/support/messages', [SupportChatController::class, 'state'])->name('support.state');
Route::get('/support/history', [SupportChatController::class, 'history'])->name('support.history');
Route::post('/support/start', [SupportChatController::class, 'start'])->name('support.start');
Route::post('/support/messages', [SupportChatController::class, 'send'])->name('support.send');

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
    Route::resource('coupons', CouponController::class)->except('show');

    // People & access
    Route::resource('customers', CustomerController::class)->except('show');
    Route::resource('users', UserController::class)->except('show');
    Route::resource('roles', RoleController::class)->except('show');

    // POS — fast counter-sale screen (Alpine cart + JSON search + SalesService).
    Route::get('pos', [PosController::class, 'index'])->name('pos.index');
    Route::get('pos/search', [PosController::class, 'search'])->name('pos.search');
    Route::post('pos', [PosController::class, 'store'])->name('pos.store');

    // Reviews — customer review moderation queue (view + approve/unapprove/delete).
    Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::patch('reviews/{review}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');
    Route::patch('reviews/{review}/unapprove', [ReviewController::class, 'unapprove'])->name('reviews.unapprove');
    Route::delete('reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    // Quotations — draft sales; "convert" turns an accepted quote into a credit order.
    Route::post('quotations/{quotation}/status', [QuotationController::class, 'status'])->name('quotations.status');
    Route::post('quotations/{quotation}/convert', [QuotationController::class, 'convert'])->name('quotations.convert');
    Route::get('quotations/{quotation}/print', [QuotationController::class, 'print'])->name('quotations.print');
    Route::resource('quotations', QuotationController::class);

    // Vendor / wholesale credit sale — POS-style on the vendor channel (deferred payment → AR).
    Route::get('vendor-sales', [VendorSaleController::class, 'index'])->name('vendor-sales.index');
    Route::get('vendor-sales/search', [VendorSaleController::class, 'search'])->name('vendor-sales.search');
    Route::post('vendor-sales', [VendorSaleController::class, 'store'])->name('vendor-sales.store');

    // Orders — view + detail + status update (no create/delete; orders come from checkout/POS).
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}/print', [OrderController::class, 'print'])->name('orders.print');
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
    Route::patch('orders/{order}/delivery', [OrderController::class, 'updateDelivery'])->name('orders.delivery');

    // Support inbox — staff side of the customer chat widget.
    Route::get('support', [SupportController::class, 'index'])->name('support.index');
    Route::get('support/conversations', [SupportController::class, 'conversations'])->name('support.conversations');
    Route::get('support/{conversation}/messages', [SupportController::class, 'messages'])->name('support.messages');
    Route::get('support/{conversation}/history', [SupportController::class, 'history'])->name('support.history');
    Route::post('support/{conversation}/reply', [SupportController::class, 'reply'])->name('support.reply');
    Route::post('support/{conversation}/delivered', [SupportController::class, 'delivered'])->name('support.delivered');
    Route::post('support/{conversation}/block', [SupportController::class, 'block'])->name('support.block');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');

    // Procurement — suppliers + purchasing (receive posts stock + moving-avg cost + ledger).
    Route::resource('suppliers', SupplierController::class)->except('show');
    Route::post('purchases/{purchase}/receive', [PurchaseController::class, 'receive'])->name('purchases.receive');
    Route::post('purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])->name('purchases.cancel');
    Route::post('purchases/{purchase}/payments', [PurchaseController::class, 'payment'])->name('purchases.payment');
    Route::get('purchases/{purchase}/print', [PurchaseController::class, 'print'])->name('purchases.print');
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

    // Blog — posts (+ many-to-many categories/tags) and the taxonomies they draw from.
    Route::prefix('blog')->name('blog.')->group(function () {
        Route::resource('posts', BlogPostController::class)->except('show');
        Route::post('categories/reorder', [BlogCategoryController::class, 'reorder'])->name('categories.reorder');
        Route::resource('categories', BlogCategoryController::class)->only(['index', 'store', 'edit', 'update', 'destroy']);
        Route::resource('tags', BlogTagController::class)->only(['index', 'store', 'edit', 'update', 'destroy']);
    });

    // Ledger — the financial source of truth (read-only): position, trial balance, entries.
    Route::get('ledger', [LedgerController::class, 'index'])->name('ledger.index');

    // Activity log — read-only audit trail of admin mutations (§23).
    Route::get('activity', [ActivityLogController::class, 'index'])->name('activity.index');

    // Gallery / media library (Livewire) — guarded; per-action checks live in the component.
    Route::view('/gallery', 'admin.gallery.index')
        ->middleware('can:gallery.view')
        ->name('gallery.index');

    // Settings — tabbed groups (CONVENTIONS §6); guards live on the controller.
    Route::get('/settings', fn () => redirect()->route('admin.settings.show', 'general'))->name('settings.index');
    Route::get('/settings/{group}', [SettingsController::class, 'show'])->name('settings.show');
    Route::put('/settings/{group}', [SettingsController::class, 'update'])->name('settings.update');
});
