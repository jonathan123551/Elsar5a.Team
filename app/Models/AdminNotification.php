<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminNotification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'category',
        'title',
        'body',
        'booking_id',
        'ticket_id',
        'target_url',
        'dedupe_key',
        'meta',
        'read_at',
        'push_sent_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'read_at' => 'datetime',
        'push_sent_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $notification): void {
            $notification->target_url = self::sanitizeTargetUrl($notification->target_url, $notification->booking_id);
        });
    }

    /**
     * Guarantee click target URLs always point to a safe internal /admin path
     * and never redirect to customer-facing or external URLs.
     */
    public static function sanitizeTargetUrl(?string $url, ?int $bookingId = null): string
    {
        $fallback = $bookingId ? "/admin/bookings/{$bookingId}" : '/admin/bookings';

        if (! $url) {
            return $fallback;
        }

        $parsedPath = parse_url($url, PHP_URL_PATH);
        if (! is_string($parsedPath) || ! str_starts_with($parsedPath, '/admin')) {
            return $fallback;
        }

        // Prevent open redirects via protocol-relative paths like //evil.com
        if (str_starts_with($parsedPath, '//')) {
            return $fallback;
        }

        return $parsedPath;
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->update(['read_at' => now()]);
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
