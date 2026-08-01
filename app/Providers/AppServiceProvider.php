<?php

namespace App\Providers;

use App\Models\Attribute;
use App\Models\BlogPost;
use App\Models\Bom;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Deal;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\Quotation;
use App\Models\Review;
use App\Models\Supplier;
use App\Models\User;
use App\Listeners\SendWelcomeEmail;
use App\Listeners\SendWelcomeSupportMessage;
use App\Observers\AuditObserver;
use App\Support\IndexNow;
use App\Support\SettingsApplier;
use App\Support\Storefront;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

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

        /*
         | Retire the cached storefront blocks whenever the catalogue moves. Covers
         | the obvious edits, and also stock: StockService writes through the model
         | (`$variant->save()`), so a sale or a delivery lands here too. Without the
         | variant hook a cached card could keep advertising an item as in stock
         | after the last one sold.
         */
        foreach ([Product::class, ProductVariant::class, Deal::class, Category::class, Brand::class] as $model) {
            $model::saved(fn () => Storefront::bumpCatalogVersion());
            $model::deleted(fn () => Storefront::bumpCatalogVersion());
        }

        /*
         | IndexNow — tell Bing/Yandex/Seznam/Naver a page changed rather than waiting
         | to be re-crawled. Fires on save, but only for records that are actually
         | public: submitting a draft would push a URL that 404s, which is worse than
         | submitting nothing. No-ops entirely unless INDEXNOW_ENABLED is set, and the
         | submission itself is queued, so nothing here touches request latency.
         */
        Product::saved(function (Product $product) {
            if ($product->is_web_listed && $product->is_active && $product->published_at?->isPast()) {
                IndexNow::submit(route('product.show', $product->slug));
            }
        });

        BlogPost::saved(function (BlogPost $post) {
            if ($post->published_at?->isPast()) {
                IndexNow::submit(route('blog.show', $post->slug));
            }
        });

        // Register the Microsoft driver for Socialite (admin SSO).
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('microsoft', \SocialiteProviders\Microsoft\Provider::class);
        });

        // Bridge admin-managed mail settings into config('mail') at runtime.
        SettingsApplier::apply();

        // On registration: send the welcome email and the email-verification link,
        // and post a welcome message from support into the customer's chat.
        Event::listen(Registered::class, SendWelcomeEmail::class);
        Event::listen(Registered::class, SendWelcomeSupportMessage::class);
        Event::listen(Registered::class, \Illuminate\Auth\Listeners\SendEmailVerificationNotification::class);
    }
}
