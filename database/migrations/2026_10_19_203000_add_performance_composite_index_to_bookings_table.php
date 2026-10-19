<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds composite index (show_time_id, status, tickets_count) to `bookings`.
 *
 * Speeds up ShowTime capacity calculations and Homepage seat aggregation
 * by allowing the database engine to perform an index-only scan rather
 * than sequential table scans during ticket sales.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['show_time_id', 'status', 'tickets_count'], 'bookings_show_time_status_tickets_idx');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_show_time_status_tickets_idx');
        });
    }
};
