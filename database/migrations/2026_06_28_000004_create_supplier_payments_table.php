<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payments made to a supplier against a purchase, recorded after the goods were
     * received (settling the payable over time). Each payment bumps the purchase's
     * `paid_total` and posts Accounts Payable (debit) ↔ Cash/Bank (credit) to the ledger.
     */
    public function up(): void
    {
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('paid_on');
            $table->string('method')->default('cash');   // cash|bank
            $table->string('reference')->nullable();      // cheque #, txn id, etc.
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('purchase_id');
            $table->index('supplier_id');
            $table->index('paid_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};
