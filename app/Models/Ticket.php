<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'booking_id',
        'name',
        'phone',
        'ticket_code',
        'qr_image_path',
        'is_scanned',
        'scanned_at',
        'scanned_by_admin_id',
        'scanned_by_ip',
        'scanned_by_ua',
        'whatsapp_sent',
        // Concurrency-control state for the WhatsApp send pipeline:
        // pending | sending | sent | failed. See the 2026_05_30
        // add_delivery_status migration + SendWhatsAppTicketImageJob.
        'delivery_status',

        // Lifecycle & Delivery Tracking
        'resend_requested_at',
        'last_delivered_at',
        'claimed_at',
        'delivery_error',
    ];

    protected $casts = [
        'is_scanned'          => 'boolean',
        'whatsapp_sent'       => 'boolean',
        'scanned_at'          => 'datetime',
        'resend_requested_at' => 'datetime',
        'last_delivered_at'   => 'datetime',
        'claimed_at'          => 'datetime',
    ];

    public function isClaimed(): bool
    {
        return !is_null($this->claimed_at);
    }

    public function isDelivered(): bool
    {
        return $this->delivery_status === 'sent' || $this->whatsapp_sent;
    }

    public function isFailed(): bool
    {
        return $this->delivery_status === 'failed';
    }

    public function isSending(): bool
    {
        return $this->delivery_status === 'sending';
    }

    public function booking()
    {
        return $this->belongsTo(\App\Models\Booking::class);
    }
}
