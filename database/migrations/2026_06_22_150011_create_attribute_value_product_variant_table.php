<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Variant <-> its defining attribute values (pivot). */
    public function up(): void
    {
        Schema::create('attribute_value_product_variant', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained('attribute_values')->cascadeOnDelete();

            $table->primary(['product_variant_id', 'attribute_value_id'], 'avpv_primary');
            $table->index('attribute_value_id');                    // storefront filtering
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_value_product_variant');
    }
};
