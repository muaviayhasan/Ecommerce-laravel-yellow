<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Quotations (§3.7 / §11): a draft sale handed to a customer before they commit.
     * May mix raw and finished variants; prices at the chosen tier. Accepting converts
     * it into an `orders` row (no stock moves until the sale itself is fulfilled).
     */
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('status')->default('draft'); // draft|sent|accepted|rejected|expired|converted
            $table->date('valid_until')->nullable();
            $table->string('price_tier')->default('retail');         // retail|wholesale
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_total', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('converted_order_id')->nullable(); // the sale it became
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('customer_id');
            $table->index('valid_until');
            $table->foreign('converted_order_id')->references('id')->on('orders')->nullOnDelete();
        });

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('name_snapshot');
            $table->text('description')->nullable();
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_total', 15, 2);

            $table->index('quotation_id');
        });

        // Wire the deferred orders.quotation_id FK now that quotations exists.
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('quotation_id')->references('id')->on('quotations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['quotation_id']);
        });
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
    }
};
