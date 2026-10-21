<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_message_status_events', function (Blueprint $table) {
            $table->id();
            $table->string('message_id', 255)->unique();
            $table->string('status', 20);
            $table->unsignedInteger('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('provider_timestamp')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_message_status_events');
    }
};
