<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The STOCKABLE + PRICED unit (§3.2 / §5). A `simple` product has one default
     * variant. Stock, moving-average cost and the two price tiers live here — never
     * on the product. Stock is mutated only via stock_movements.
     */
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('sku')->unique();

            // Pricing — two tiers; markup default + manual override (§9)
            $table->decimal('cost', 12, 2)->default(0);              // MOVING-AVERAGE unit cost (system-maintained)
            $table->decimal('retail_price', 12, 2)->default(0);      // web + POS
            $table->decimal('wholesale_price', 12, 2)->nullable();   // vendor channel
            $table->decimal('compare_at_price', 12, 2)->nullable();  // strikethrough "was"
            $table->boolean('price_is_manual')->default(false);      // true = keep entered prices, ignore markup

            // Stock — mutated only via stock_movements (§8)
            $table->decimal('stock_quantity', 12, 3)->default(0);
            $table->decimal('reserved_quantity', 12, 3)->default(0); // held by unpaid orders
            $table->decimal('low_stock_threshold', 12, 3)->default(0);

            $table->decimal('weight', 8, 3)->nullable();
            $table->string('barcode')->nullable();
            $table->foreignId('image_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);           // shown first
            $table->timestamps();

            $table->index('product_id');
            $table->index('is_active');
            $table->index('retail_price');
            $table->index('stock_quantity');
            $table->index('barcode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
