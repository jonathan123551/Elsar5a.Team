<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration intentionally does not infer delivery from last_delivered_at.
     * That timestamp has historically been written on API acceptance as well as
     * from Meta delivery webhooks, so it is not reliable evidence for backfills.
     */
    public function up(): void
    {
        // No safe conversion is possible without retained Meta webhook history.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No data was changed.
    }
};
