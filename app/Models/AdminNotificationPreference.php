<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminNotificationPreference extends Model
{
    public const CATEGORIES = [
        'new_bookings',
        'payment_proofs',
        'whatsapp_failures',
        'ticket_delivery',
        'completed_bookings',
    ];

    protected $fillable = [
        'user_id',
        'new_bookings',
        'payment_proofs',
        'whatsapp_failures',
        'ticket_delivery',
        'completed_bookings',
    ];

    protected $casts = [
        'new_bookings' => 'boolean',
        'payment_proofs' => 'boolean',
        'whatsapp_failures' => 'boolean',
        'ticket_delivery' => 'boolean',
        'completed_bookings' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function forUser(int $userId): self
    {
        return static::firstOrCreate(
            ['user_id' => $userId],
            [
                'new_bookings' => true,
                'payment_proofs' => false,
                'whatsapp_failures' => false,
                'ticket_delivery' => false,
                'completed_bookings' => false,
            ]
        );
    }

    public function isCategoryEnabled(string $category): bool
    {
        if (! in_array($category, self::CATEGORIES, true)) {
            return true;
        }

        return (bool) $this->getAttribute($category);
    }
}
