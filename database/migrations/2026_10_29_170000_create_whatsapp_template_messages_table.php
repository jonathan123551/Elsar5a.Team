<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Maps each dispatched WhatsApp template WAMID to its booking and recipient phone.
     * Unlike `bookings.template_message_id` which gets overwritten when multiple template
     * messages are sent for different ticket holders, this ledger keeps every dispatched
     * template WAMID permanently indexed for deterministic CTA correlation.
     */
    public function up(): void
    {
        Schema::create('whatsapp_template_messages', function (Blueprint $table) {
            $table->id();
            $table->string('message_id', 255)->unique();
            $table->unsignedBigInteger('booking_id')->nullable()->index();
            $table->string('phone', 50)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_template_messages');
    }
};
