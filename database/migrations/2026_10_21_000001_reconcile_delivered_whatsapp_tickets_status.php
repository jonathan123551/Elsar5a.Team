<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Reconciles historical tickets where SendWhatsAppTicketImageJob or direct send
     * successfully dispatched the QR ticket image to Meta (whatsapp_sent = true AND last_delivered_at IS NOT NULL)
     * but left delivery_status as 'sent', aligning database state with actual recipient delivery (such as Booking #20).
     */
    public function up(): void
    {
        DB::table('tickets')
            ->where('whatsapp_sent', true)
            ->whereNotNull('last_delivered_at')
            ->where('delivery_status', 'sent')
            ->update([
                'delivery_status' => 'delivered',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-destructive: historical delivery state is preserved.
    }
};
