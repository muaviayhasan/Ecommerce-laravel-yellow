<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Coupon targeting: a "first order only" (welcome) flag, plus an optional
 * customer allow-list. A coupon with rows in coupon_customer is private to those
 * customers; with none it stays public.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->boolean('first_order_only')->default(false)->after('usage_limit_per_customer');
        });

        Schema::create('coupon_customer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->unique(['coupon_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_customer');

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn('first_order_only');
        });
    }
};
