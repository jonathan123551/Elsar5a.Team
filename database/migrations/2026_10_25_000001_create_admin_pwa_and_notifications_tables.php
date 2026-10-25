<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('push_subscriptions')) {
            Schema::create('push_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->text('endpoint');
                $table->string('endpoint_hash', 64)->unique();
                $table->string('public_key', 255);
                $table->string('auth_token', 255);
                $table->string('content_encoding', 32)->default('aes128gcm');
                $table->string('user_agent', 500)->nullable();
                $table->string('device_label', 120)->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'updated_at'], 'push_subscriptions_user_updated_idx');
            });
        }

        if (! Schema::hasTable('admin_notifications')) {
            Schema::create('admin_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('type', 64)->index();
                $table->string('category', 64)->index();
                $table->string('title', 255);
                $table->text('body');
                $table->unsignedBigInteger('booking_id')->nullable()->index();
                $table->unsignedBigInteger('ticket_id')->nullable()->index();
                $table->string('target_url', 255);
                $table->string('dedupe_key', 191)->nullable();
                $table->text('meta')->nullable();
                $table->timestamp('read_at')->nullable()->index();
                $table->timestamp('push_sent_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'dedupe_key'], 'admin_notifications_user_dedupe_unique');
                $table->index(['user_id', 'read_at', 'created_at'], 'admin_notifications_inbox_idx');
            });
        }

        if (! Schema::hasTable('admin_notification_preferences')) {
            Schema::create('admin_notification_preferences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->boolean('new_bookings')->default(true);
                $table->boolean('payment_proofs')->default(true);
                $table->boolean('whatsapp_failures')->default(true);
                $table->boolean('ticket_delivery')->default(true);
                $table->boolean('completed_bookings')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_notification_preferences');
        Schema::dropIfExists('admin_notifications');
        Schema::dropIfExists('push_subscriptions');
    }
};
