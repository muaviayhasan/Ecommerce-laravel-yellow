<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data fix: SalesService used to stamp fully-paid sales with status 'paid',
 * which is not an order status (payment state lives in payment_status). Those
 * rows rendered an unstyled badge and sat outside the status flow. Counter
 * sales (POS / vendor) become 'completed' — goods were handed over at sale
 * time; any web rows become 'processing' — paid but still to be fulfilled.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')
            ->where('status', 'paid')
            ->whereIn('channel', ['pos', 'vendor'])
            ->update(['status' => 'completed']);

        DB::table('orders')
            ->where('status', 'paid')
            ->update(['status' => 'processing']);
    }

    public function down(): void
    {
        // Irreversible by design: 'paid' was an invalid status and the original
        // rows cannot be distinguished after normalisation. Nothing to undo.
    }
};
