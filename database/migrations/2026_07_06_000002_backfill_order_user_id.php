<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Historic orders were created without a user_id (only customer_id), so they never
     * appeared in the customer's "My Account" area. Link each order to its customer's
     * account where that customer is tied to a user.
     */
    public function up(): void
    {
        DB::statement('
            UPDATE orders o
            JOIN customers c ON c.id = o.customer_id
            SET o.user_id = c.user_id
            WHERE o.user_id IS NULL AND c.user_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        // No-op: backfilled rows can't be reliably told apart from natively-linked ones.
    }
};
