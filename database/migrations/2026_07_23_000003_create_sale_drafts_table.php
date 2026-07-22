<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parked sales: the POS / vendor-sale cart saved server-side so a delayed
 * customer's order can be resumed later (from any till). The payload is the
 * full front-end cart state; a resumed draft is deleted when its sale completes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_drafts', function (Blueprint $table) {
            $table->id();
            $table->string('channel');                 // pos|vendor
            $table->string('label', 120)->nullable();  // e.g. "Muavia · 4:15 PM"
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->json('payload');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['channel', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_drafts');
    }
};
