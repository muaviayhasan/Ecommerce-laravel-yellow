<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * UNIFIED ITEMS (§3.1 / §4): raw materials, manufactured goods, trading goods and
     * services all live here, separated by `type` + capability flags. `is_web_listed`
     * gates what shows in the public storefront. Stock/cost/price live on the variant.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->unique();

            // Item type + capability flags (defaults follow `type`, overridable per item)
            $table->string('type')->default('trading');              // trading|manufactured|raw|service
            $table->boolean('is_stock_tracked')->default(true);      // false for services
            $table->boolean('is_purchasable')->default(true);        // can appear on a purchase order
            $table->boolean('is_manufacturable')->default(false);    // has a BOM
            $table->boolean('is_sellable')->default(true);           // can be sold on any channel
            $table->boolean('is_web_listed')->default(false);        // * shows in the PUBLIC storefront
            $table->string('manufacture_mode')->nullable();          // to_stock|to_order (if manufacturable)
            $table->string('variant_mode')->default('simple');       // simple|variable

            $table->string('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->json('specifications')->nullable();              // spec rows for the product Spec tab
            $table->decimal('base_price', 12, 2)->nullable();        // display "from" = min(active variant retail)
            $table->decimal('markup_percent', 5, 2)->nullable();     // item-level markup override (pricing)

            // Extra product info (design audit — warranty/returns/video/dimensions)
            $table->string('warranty')->nullable();
            $table->text('return_policy')->nullable();
            $table->string('video_url')->nullable();
            $table->decimal('length', 8, 3)->nullable();
            $table->decimal('width', 8, 3)->nullable();
            $table->decimal('height', 8, 3)->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();           // null = draft

            // SEO (§14)
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->foreignId('og_image_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('canonical_url')->nullable();
            $table->boolean('no_index')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('category_id');
            $table->index('brand_id');
            $table->index('is_active');
            $table->index('is_sellable');
            $table->index('is_web_listed');
            $table->index('is_featured');
            $table->index('published_at');
            // website catalog query: is_web_listed AND is_active AND published_at <= now() [AND category]
            $table->index(['is_web_listed', 'is_active', 'published_at', 'category_id'], 'products_web_catalog_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
