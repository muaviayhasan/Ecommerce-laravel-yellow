<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Deals: named promotions bundling variants across products (admin-managed;
     * storefront wiring comes later). Two types share one schema —
     * `bundle` sells the whole set at bundle_price, `sale` prices items
     * individually via deal_items.deal_price.
     */
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('image_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('type')->default('sale');                 // bundle|sale (string per §2.3)
            $table->decimal('bundle_price', 12, 2)->nullable();      // bundle type only
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index(['starts_at', 'ends_at']);
            $table->index('sort_order');
        });

        Schema::create('deal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained('deals')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->decimal('quantity', 10, 3)->default(1);          // per-set quantity (bundles)
            $table->decimal('deal_price', 12, 2)->nullable();        // per-item price (sale type)
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['deal_id', 'product_variant_id']);
            $table->index('product_variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_items');
        Schema::dropIfExists('deals');
    }
};
