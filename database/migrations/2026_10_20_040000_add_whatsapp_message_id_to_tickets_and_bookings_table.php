<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds Meta WhatsApp message IDs for asynchronous webhook status tracking:
     * - tickets: whatsapp_message_id
     * - bookings: template_message_id
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'whatsapp_message_id')) {
                $table->string('whatsapp_message_id', 100)->nullable()->index()->after('whatsapp_sent');
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'template_message_id')) {
                $table->string('template_message_id', 100)->nullable()->index()->after('template_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'whatsapp_message_id')) {
                $table->dropColumn('whatsapp_message_id');
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'template_message_id')) {
                $table->dropColumn('template_message_id');
            }
        });
    }
};
