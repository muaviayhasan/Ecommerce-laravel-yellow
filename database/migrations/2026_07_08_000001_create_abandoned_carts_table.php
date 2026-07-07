<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persistent snapshot of a cart that reached checkout but wasn't paid for, keyed
 * by the shopper's email. The storefront cart itself is session-only, so this
 * table is what the recovery scheduler scans to send "you left something behind"
 * reminders. One open row per email; a completed order flips recovered_at.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abandoned_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email');
            $table->string('name')->nullable();
            $table->json('items');                         // [{variant_id, name, sku, qty, price, image, url}]
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->unsignedInteger('item_count')->default(0);
            $table->string('token', 64)->unique();         // unguessable recover-link handle
            $table->unsignedTinyInteger('reminders_sent')->default(0);
            $table->timestamp('last_reminded_at')->nullable();
            $table->timestamp('recovered_at')->nullable();
            $table->timestamps();

            $table->index('email');
            $table->index('last_reminded_at');
            // The scheduler scans by "still open, not fully reminded, idle since X".
            $table->index(['recovered_at', 'reminders_sent', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abandoned_carts');
    }
};
