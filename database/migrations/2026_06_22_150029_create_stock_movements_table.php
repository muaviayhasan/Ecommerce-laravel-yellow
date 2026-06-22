<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Inventory audit trail (§10) — logs every stock change with reason, delta and
     * resulting balance. Never mutate variant stock silently. Polymorphic reference
     * links the movement to its source (purchase, order, adjustment, return).
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->string('type');                                 // incoming|outgoing|adjustment
            $table->string('reason')->nullable();                   // purchase|sale|return|damage|correction
            $table->integer('quantity_change');                     // signed delta (+/-)
            $table->integer('balance_after');                       // resulting stock
            $table->string('location')->nullable();                 // optional warehouse label
            $table->string('reference_type')->nullable();           // polymorphic source
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('product_variant_id');
            $table->index('type');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
