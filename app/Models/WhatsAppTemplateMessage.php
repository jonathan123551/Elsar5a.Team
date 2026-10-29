<?php

namespace App\Models;

use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class WhatsAppTemplateMessage extends Model
{
    protected $table = 'whatsapp_template_messages';

    protected $fillable = [
        'message_id',
        'booking_id',
        'phone',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Durably record a dispatched template message WAMID mapped to its booking and recipient phone.
     */
    public static function record(?string $messageId, ?int $bookingId, ?string $phone): ?self
    {
        if (! $messageId || ! Schema::hasTable('whatsapp_template_messages')) {
            return null;
        }

        $canonicalPhone = $phone ? PhoneNumber::canonical($phone) : '';

        return self::updateOrCreate(
            ['message_id' => $messageId],
            [
                'booking_id' => $bookingId,
                'phone' => $canonicalPhone,
            ]
        );
    }

    /**
     * Find a recorded template message dispatch by Meta WAMID.
     */
    public static function findByMessageId(?string $messageId): ?self
    {
        if (! $messageId || ! Schema::hasTable('whatsapp_template_messages')) {
            return null;
        }

        return self::where('message_id', $messageId)->first();
    }
}
