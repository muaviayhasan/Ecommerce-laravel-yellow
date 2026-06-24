<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

            // Snapshots — keep historical orders readable if a variant changes/deactivates.
            $table->string('name_snapshot');
            $table->string('sku_snapshot');
            $table->json('attributes_snapshot')->nullable();

            $table->decimal('unit_price', 12, 2);
            $table->decimal('quantity', 12, 3);
            $table->decimal('line_total', 15, 2);
            $table->decimal('cost_snapshot', 12, 2)->nullable();     // moving-avg cost at sale -> COGS

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
