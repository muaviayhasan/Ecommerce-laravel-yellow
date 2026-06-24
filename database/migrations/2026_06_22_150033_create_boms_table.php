<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bill of Materials (§3.4 / §7): the recipe for a manufacturable product — which
     * component variants and how many are consumed to produce `output_quantity`
     * finished units, plus labor and overhead.
     */
    public function up(): void
    {
        Schema::create('boms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete(); // BOM for a specific finished variant
            $table->string('name')->nullable();
            $table->decimal('output_quantity', 12, 3)->default(1);   // finished units produced per run
            $table->decimal('labor_cost', 12, 2)->default(0);
            $table->decimal('overhead_cost', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('version')->default(1);
            $table->timestamps();

            $table->index('product_id');
            $table->index('is_active');
        });

        Schema::create('bom_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')->constrained('boms')->cascadeOnDelete();
            $table->foreignId('component_variant_id')->constrained('product_variants')->restrictOnDelete(); // a raw/trading variant
            $table->decimal('quantity', 12, 3);
            $table->decimal('waste_percent', 5, 2)->default(0);      // material lost in assembly
            $table->timestamps();

            $table->index('bom_id');
            $table->index('component_variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bom_items');
        Schema::dropIfExists('boms');
    }
};
