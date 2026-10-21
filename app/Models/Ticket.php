<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
        'whatsapp_message_id',
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

    /**
     * True when the actual QR ticket image has been delivered/received by the customer
     * only when Meta confirms delivery (or read) for the exact message ID.
     */
    public function isTicketDeliveredOrReceived(): bool
    {
        if (in_array($this->delivery_status, ['delivered', 'read'], true)) {
            return true;
        }

        return false;
    }

    /**
     * True when the message is sent to network but not yet confirmed delivered/received.
     */
    public function isPendingNetworkDelivery(): bool
    {
        return $this->delivery_status === 'sent' && ! $this->isTicketDeliveredOrReceived();
    }

    public function isDelivered(): bool
    {
        return $this->isTicketDeliveredOrReceived();
    }

    public function isConfirmedDelivered(): bool
    {
        return in_array($this->delivery_status, ['delivered', 'read'], true);
    }

    public function isSent(): bool
    {
        return in_array($this->delivery_status, ['sent', 'delivered', 'read'], true) || $this->whatsapp_sent;
    }

    public function isFailed(): bool
    {
        return $this->delivery_status === 'failed';
    }

    public function isSending(): bool
    {
        return $this->delivery_status === 'sending';
    }

    /** Persist Meta's synchronous acceptance without allowing a racing webhook to be downgraded. */
    public function markWhatsAppAccepted(?string $messageId): void
    {
        $resolvedMessageId = $messageId ?: $this->whatsapp_message_id;
        DB::table($this->getTable())->where('id', $this->getKey())->update([
            'whatsapp_sent' => true,
            'delivery_status' => DB::raw("CASE WHEN delivery_status IN ('delivered', 'read') THEN delivery_status ELSE 'sent' END"),
            'whatsapp_message_id' => $resolvedMessageId,
            'delivery_error' => null,
            'updated_at' => now(),
        ]);
        WhatsAppMessageStatusEvent::applyPendingForMessage($resolvedMessageId);
    }

    /** Record a terminal send failure without overwriting a webhook-confirmed delivery. */
    public function markWhatsAppFailed(string $error): void
    {
        DB::table($this->getTable())->where('id', $this->getKey())
            ->whereNotIn('delivery_status', ['delivered', 'read'])
            ->update([
                'whatsapp_sent' => false,
                'delivery_status' => 'failed',
                'delivery_error' => $error,
                'updated_at' => now(),
            ]);
    }

    /** Return to waiting for a customer CTA after a template recovery, monotonically. */
    public function markAwaitingCustomerClick(?string $reason): void
    {
        DB::table($this->getTable())->where('id', $this->getKey())
            ->whereNotIn('delivery_status', ['delivered', 'read'])
            ->update([
                'whatsapp_sent' => false,
                'delivery_status' => 'pending',
                'claimed_at' => null,
                'delivery_error' => $reason,
                'updated_at' => now(),
            ]);
    }

    public function booking()
    {
        return $this->belongsTo(\App\Models\Booking::class);
    }
}
