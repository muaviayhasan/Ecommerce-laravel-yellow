<?php

namespace App\Providers;

use App\Models\Attribute;
use App\Models\BlogPost;
use App\Models\Bom;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Quotation;
use App\Models\Review;
use App\Models\Supplier;
use App\Models\User;
use App\Observers\AuditObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The aggregate-root models whose create/update/delete are audited.
     *
     * @var list<class-string<\Illuminate\Database\Eloquent\Model>>
     */
    private const AUDITED = [
        Product::class, Category::class, Brand::class, Attribute::class,
        Coupon::class, Customer::class, Supplier::class, Purchase::class,
        Order::class, Quotation::class, Bom::class, BlogPost::class,
        Review::class, User::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // CONVENTIONS §4.1 — super-admin bypasses every permission check.
        Gate::before(function ($user) {
            return $user->hasRole('super-admin') ? true : null;
        });

        // §23 audit logging — record admin mutations on the core entities.
        foreach (self::AUDITED as $model) {
            $model::observe(AuditObserver::class);
        }
    }
}
