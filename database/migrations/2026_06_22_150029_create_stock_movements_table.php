<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SINGLE SOURCE OF TRUTH for stock (§3.5 / §8). Append-only; every change is one
     * signed row carrying balance_after, unit_cost and a polymorphic reference. Never
     * mutate product_variants.stock_quantity directly.
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            // purchase_in|sale_out|production_consume|production_output|adjustment|return_in|transfer
            $table->string('type');
            $table->decimal('quantity', 12, 3);                      // signed: + adds stock, - removes
            $table->decimal('balance_after', 12, 3);                 // resulting on-hand for audit
            $table->decimal('unit_cost', 12, 2)->nullable();         // cost at the moment of movement
            $table->string('reference_type')->nullable();           // polymorphic source
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('product_variant_id');
            $table->index('type');
            $table->index(['reference_type', 'reference_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
