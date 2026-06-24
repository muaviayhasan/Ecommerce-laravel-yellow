<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The UNIFIED SALE document (§3.8 / §12): one row per sale across all channels
     * (web / pos / vendor). Quotations convert into this same document.
     *
     * customer_id / quotation_id are added here as indexed columns; their FK
     * constraints are wired in the customers and quotations migrations (which run
     * later) so migrate runs in a clean dependency order.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('channel')->default('web');               // web|pos|vendor
            $table->foreignId('customer_id')->nullable()->index();   // FK added in customers migration
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // web auth account
            $table->unsignedBigInteger('quotation_id')->nullable()->index(); // FK added in quotations migration
            $table->string('price_tier')->default('retail');         // retail|wholesale
            $table->string('status')->default('pending');
            // pending|paid|processing|shipped|delivered|completed|cancelled|refunded
            $table->string('payment_method')->default('cod');        // cod|card|qr|cash|bank|credit
            $table->string('payment_status')->default('unpaid');     // unpaid|partial|paid|partially_refunded|refunded

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_total', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('shipping_total', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->decimal('paid_total', 15, 2)->default(0);

            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->string('currency', 3)->default('PKR');

            // Fulfilment / tracking (from design audit — order tracking screen)
            $table->string('shipping_method')->nullable();
            $table->string('courier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->date('estimated_delivery_date')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('customer_notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('ip_address', 45)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); // staff (pos/vendor)
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();

            $table->index('channel');
            $table->index('status');
            $table->index('payment_status');
            $table->index('user_id');
            $table->index('placed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
