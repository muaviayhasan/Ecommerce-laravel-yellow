<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('gateway')->default('cod');              // cod|jazzcash|easypaisa|manual_qr
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending');           // pending|succeeded|failed|refunded
            $table->string('transaction_ref')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
