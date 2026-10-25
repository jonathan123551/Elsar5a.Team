<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('admin_notification_preferences')) {
            DB::table('admin_notification_preferences')->update([
                'new_bookings' => true,
                'payment_proofs' => false,
                'whatsapp_failures' => false,
                'ticket_delivery' => false,
                'completed_bookings' => false,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed
    }
};
