<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Production runs (§3.4 / §7): an assembly run consumes components and produces
     * finished stock. On completion it writes stock_movements (consume + output),
     * computes the finished unit cost, and posts to the ledger.
     */
    public function up(): void
    {
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('production_number')->unique();
            $table->foreignId('bom_id')->constrained('boms')->restrictOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->restrictOnDelete(); // finished variant produced
            $table->decimal('quantity', 12, 3);                      // finished units to produce
            $table->string('status')->default('draft');             // draft|completed|cancelled
            $table->decimal('total_component_cost', 15, 2)->default(0);
            $table->decimal('labor_cost', 12, 2)->default(0);
            $table->decimal('overhead_cost', 12, 2)->default(0);
            $table->decimal('unit_cost', 12, 2)->default(0);         // resulting finished unit cost
            $table->timestamp('produced_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('product_variant_id');
            $table->index('bom_id');
        });

        Schema::create('production_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
            $table->foreignId('component_variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('line_cost', 15, 2);

            $table->index('production_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_consumptions');
        Schema::dropIfExists('production_orders');
    }
};
