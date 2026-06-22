<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** ONE row per unique attribute-value combination. Price & stock live here. */
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->decimal('price', 12, 2)->default(0);             // actual sale price
            $table->decimal('compare_at_price', 12, 2)->nullable();  // strikethrough "was"
            $table->decimal('cost_price', 12, 2)->nullable();        // COGS feed (§11)
            $table->integer('stock_quantity')->default(0);
            $table->integer('low_stock_threshold')->default(0);
            $table->decimal('weight', 8, 3)->nullable();
            $table->string('barcode')->nullable();
            $table->foreignId('image_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);          // shown first
            $table->timestamps();

            $table->index('product_id');
            $table->index('is_active');
            $table->index('price');
            $table->index('stock_quantity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
