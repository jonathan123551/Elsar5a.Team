<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds fine-grained lifecycle and recovery tracking:
     * - tickets: resend_requested_at, last_delivered_at, claimed_at, delivery_error
     * - bookings: template_status, template_sent_at, template_error
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'resend_requested_at')) {
                $table->timestamp('resend_requested_at')->nullable()->after('delivery_status');
            }
            if (!Schema::hasColumn('tickets', 'last_delivered_at')) {
                $table->timestamp('last_delivered_at')->nullable()->after('resend_requested_at');
            }
            if (!Schema::hasColumn('tickets', 'claimed_at')) {
                $table->timestamp('claimed_at')->nullable()->after('last_delivered_at');
            }
            if (!Schema::hasColumn('tickets', 'delivery_error')) {
                $table->text('delivery_error')->nullable()->after('claimed_at');
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'template_status')) {
                $table->string('template_status')->default('not_sent')->index()->after('whatsapp_sent_at');
            }
            if (!Schema::hasColumn('bookings', 'template_sent_at')) {
                $table->timestamp('template_sent_at')->nullable()->after('template_status');
            }
            if (!Schema::hasColumn('bookings', 'template_error')) {
                $table->text('template_error')->nullable()->after('template_sent_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach (['resend_requested_at', 'last_delivered_at', 'claimed_at', 'delivery_error'] as $col) {
                if (Schema::hasColumn('tickets', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            try {
                $table->dropIndex(['template_status']);
            } catch (\Throwable $e) {}

            $columnsToDrop = [];
            foreach (['template_status', 'template_sent_at', 'template_error'] as $col) {
                if (Schema::hasColumn('bookings', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
