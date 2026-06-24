<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The buyer record for all channels (§3.6 / §10). Web sign-ups link to a `users`
     * auth row; POS walk-ins use a default customer; vendors are type=wholesale and
     * price at the wholesale tier. Credit/vendor sales accrue a receivable.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // linked web auth account
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('type')->default('retail');               // retail|wholesale (wholesale = vendor)
            $table->string('price_tier')->default('retail');         // retail|wholesale
            $table->decimal('opening_balance', 15, 2)->default(0);   // receivable for credit/vendor sales
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('user_id');
            $table->index('is_active');
        });

        // Wire the deferred orders.customer_id FK now that customers exists.
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });
        Schema::dropIfExists('customers');
    }
};
