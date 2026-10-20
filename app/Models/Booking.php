<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Booking extends Model
{
    public const STATUS_PENDING   = 'pending';
    public const STATUS_APPROVED  = 'approved';
    public const STATUS_CONFIRMED = 'approved';
    public const STATUS_REJECTED  = 'rejected';

    protected $fillable = [
        'show_time_id',
        'show_id',
        'play_id',
        'public_number',
        'full_name',
        'customer_name',
        'phone',
        'customer_phone',
        'tickets_count',
        'seats_count',
        'total_price',
        'total_amount',

        // الدفع
        'payment_method',
        'payment_status',
        'transfer_screenshot_path',
        'paid_at',

        // الحجز
        'status',
        'reference_code',
        'reference',
        'qr_code_path',
        'admin_notes',

        // الإدارة
        'approved_by_admin_id',
        'approved_at',

        // واتساب
        'whatsapp_sent',
        'whatsapp_sent_at',

        // WhatsApp Template Delivery Tracking
        'template_status',
        'template_sent_at',
        'template_error',
        'template_message_id',

        'transfer_screenshot_public_id',
        'qr_code_public_id',
    ];

    public function setPlayIdAttribute($value): void
    {
        $this->attributes['show_id'] = $value;
    }

    public function setCustomerNameAttribute($value): void
    {
        $this->attributes['full_name'] = $value;
    }

    public function setCustomerPhoneAttribute($value): void
    {
        $this->attributes['phone'] = $value;
    }

    public function setSeatsCountAttribute($value): void
    {
        $this->attributes['tickets_count'] = $value;
    }

    public function setTotalAmountAttribute($value): void
    {
        $this->attributes['total_price'] = $value;
    }

    public function setReferenceAttribute($value): void
    {
        $this->attributes['reference_code'] = $value;
    }

    public function setStatusAttribute($value): void
    {
        if ($value === 'confirmed') {
            $this->attributes['status'] = 'approved';
        } else {
            $this->attributes['status'] = $value;
        }
    }

    protected $casts = [
        'paid_at'           => 'datetime',
        'approved_at'       => 'datetime',
        'whatsapp_sent_at'  => 'datetime',
        'template_sent_at'  => 'datetime',
        'public_number'     => 'integer',
    ];

    public function isTemplateSent(): bool
    {
        return in_array($this->template_status, ['sent', 'delivered'], true);
    }

    public function isTemplateDelivered(): bool
    {
        return $this->template_status === 'delivered';
    }

    public function isTemplateFailed(): bool
    {
        return $this->template_status === 'failed';
    }

    public function isTemplateSending(): bool
    {
        return $this->template_status === 'sending';
    }

    public function ticketsDeliveredCount(): int
    {
        return $this->tickets->filter(fn($t) => $t->delivery_status === 'delivered')->count();
    }

    public function ticketsSentCount(): int
    {
        return $this->tickets->filter(fn($t) => $t->delivery_status === 'sent')->count();
    }

    public function ticketsSendingCount(): int
    {
        return $this->tickets->filter(fn($t) => $t->delivery_status === 'sending')->count();
    }

    public function ticketsFailedCount(): int
    {
        return $this->tickets->filter(fn($t) => $t->delivery_status === 'failed')->count();
    }

    public function ticketsTotalCount(): int
    {
        return $this->tickets->count() ?: ($this->tickets_count ?? 1);
    }

    /**
     * Comprehensive Delivery Badge Resolution
     *
     * Ensures consistent state representation across Admin desktop table,
     * mobile cards, booking details, and live polling endpoints.
     *
     * States:
     *  - rejected: '—' (muted)
     *  - pending: 'بانتظار الاعتماد' (sky)
     *  - approved + all delivered: 'تم الاستلام (X/Y)' (emerald)
     *  - approved + failure: 'فشل التسليم' (rose)
     *  - approved + active sending: 'جاري الإرسال…' (amber with spinner)
     *  - approved + tickets sent to Meta: 'أُرسلت للشبكة (X/Y)' (cyan/teal, no spinner)
     *  - approved + template sent: 'بانتظار نقر العميل' (sky, gentle pulse)
     *  - approved + template claimed: 'جاري التجهيز' (amber)
     *  - approved + not sent yet: 'معتمد (لم يُرسل)' (gray)
     */
    public function getDeliveryBadgeAttribute(): array
    {
        $total = $this->ticketsTotalCount();
        $delivered = $this->ticketsDeliveredCount();
        $sending = $this->ticketsSendingCount();
        $sent = $this->ticketsSentCount();
        $failed = $this->ticketsFailedCount();
        $hasFailed = $failed > 0 || $this->template_status === 'failed';
        $isClaimed = $this->tickets->filter(fn($t) => !is_null($t->claimed_at))->count() > 0;

        if ($this->status !== 'approved') {
            if ($this->status === 'rejected') {
                return [
                    'text' => '—',
                    'type' => 'muted',
                    'bg' => 'bg-white/5 text-slate-500 border border-white/10',
                    'has_spinner' => false,
                    'title' => 'حجز مرفوض',
                ];
            }
            return [
                'text' => 'بانتظار الاعتماد',
                'type' => 'pending',
                'bg' => 'bg-sky-500/10 text-sky-300 border border-sky-500/30',
                'has_spinner' => false,
                'title' => 'بانتظار اعتماد الحجز أولاً',
            ];
        }

        // 1. All tickets confirmed delivered
        if ($delivered === $total && $total > 0) {
            return [
                'text' => "تم الاستلام ({$delivered}/{$total})",
                'type' => 'emerald',
                'bg' => 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/40',
                'has_spinner' => false,
                'title' => 'تم تسليم جميع التذاكر للعميل ومؤكد من واتساب',
            ];
        }

        // 2. Any failure (template or ticket)
        if ($hasFailed) {
            $errorMsg = $this->template_error
                ?? $this->tickets->where('delivery_status', 'failed')->first()?->delivery_error
                ?? 'تعذر إرسال بعض التذاكر عبر واتساب';
            return [
                'text' => 'فشل التسليم',
                'type' => 'rose',
                'bg' => 'bg-rose-500/15 text-rose-300 border border-rose-500/40',
                'has_spinner' => false,
                'title' => $errorMsg,
            ];
        }

        // 3. Actively sending in flight (queued / HTTP request in progress)
        if ($sending > 0 || $this->template_status === 'sending') {
            return [
                'text' => 'جاري الإرسال…',
                'type' => 'amber',
                'bg' => 'bg-amber-500/15 text-amber-200 border border-amber-500/40',
                'has_spinner' => true,
                'title' => 'جاري المعالجة والإرسال لخوادم Meta',
            ];
        }

        // 4. Template sent, waiting for customer tap on WhatsApp
        if ($this->template_status === 'sent') {
            return [
                'text' => 'بانتظار نقر العميل',
                'type' => 'sky',
                'bg' => 'bg-sky-500/15 text-sky-300 border border-sky-500/40',
                'has_spinner' => false,
                'title' => 'أُرسل قالب الاستلام، بانتظار نقر العميل على واتساب',
            ];
        }

        // 5. Customer clicked and claimed, waiting for ticket dispatch
        if ($isClaimed && $sent === 0) {
            return [
                'text' => 'جاري التجهيز',
                'type' => 'amber',
                'bg' => 'bg-amber-500/15 text-amber-300 border border-amber-500/40',
                'has_spinner' => false,
                'title' => 'نقر العميل وبانتظار بدء إرسال التذاكر',
            ];
        }

        // 6. Tickets sent to Meta network (HTTP 200 accepted), waiting for recipient delivery
        if ($sent > 0) {
            return [
                'text' => "أُرسلت للشبكة ({$sent}/{$total})",
                'type' => 'teal',
                'bg' => 'bg-cyan-500/15 text-cyan-300 border border-cyan-500/40',
                'has_spinner' => false,
                'title' => 'أُرسلت التذاكر إلى خوادم واتساب بنجاح، بانتظار استلام جهاز العميل',
            ];
        }

        // 7. Approved but no template or tickets sent yet
        return [
            'text' => 'معتمد (لم يُرسل)',
            'type' => 'gray',
            'bg' => 'bg-white/5 text-slate-400 border border-white/10',
            'has_spinner' => false,
            'title' => 'الحجز معتمد ولكن لم يتم إرسال التذاكر بعد',
        ];
    }

    /**
     * Atomically allocate the next public booking number for a given
     * show.
     *
     * The whole operation is one Postgres statement:
     *
     *   INSERT INTO show_booking_counters (...)
     *   VALUES (?, 1, NOW(), NOW())
     *   ON CONFLICT (show_id)
     *   DO UPDATE SET last_number = show_booking_counters.last_number + 1,
     *                 updated_at  = NOW()
     *   RETURNING last_number;
     *
     * Why this is safe:
     *   * The ON CONFLICT branch locks the counter row at the
     *     row level for the duration of the statement, so two
     *     concurrent bookings on the same show serialise — one
     *     returns N, the other returns N + 1.
     *   * The whole call runs inside the caller's transaction
     *     (BookingController@store opens one). If anything fails
     *     after this call but before commit, the counter
     *     increment is rolled back, so no numbers are burned by
     *     failed bookings.
     *   * The UNIQUE (show_id, public_number) constraint on
     *     `bookings` is a belt-and-braces safety net: if anything
     *     ever does attempt a duplicate (e.g. a manual SQL
     *     intervention), Postgres rejects the insert and the
     *     transaction rolls back.
     */
    public static function allocatePublicNumber(int $showId): int
    {
        if (!self::supportsPublicNumbers()) {
            return 0;
        }

        $row = DB::selectOne(
            <<<'SQL'
            INSERT INTO show_booking_counters
                   (show_id, last_number, created_at, updated_at)
            VALUES (?, 1, NOW(), NOW())
            ON CONFLICT (show_id)
            DO UPDATE SET last_number = show_booking_counters.last_number + 1,
                          updated_at  = NOW()
            RETURNING last_number
            SQL,
            [$showId],
        );

        return (int) $row->last_number;
    }

    public static function hasShowIdColumn(): bool
    {
        return Schema::hasColumn('bookings', 'show_id');
    }

    public static function supportsPublicNumbers(): bool
    {
        return DB::getDriverName() === 'pgsql'
            && Schema::hasTable('show_booking_counters')
            && Schema::hasColumn('bookings', 'show_id')
            && Schema::hasColumn('bookings', 'public_number');
    }

    /**
     * Render-ready string for the booking number (`"#42"`).
     * Falls back to the DB id for any legacy row that somehow
     * still has a NULL public_number — should never happen after
     * the backfill migration, but defensive UI rendering keeps
     * the admin pages from showing "#" with nothing after it.
     */
    protected function displayNumber(): Attribute
    {
        return Attribute::make(
            get: fn () => '#' . ($this->public_number ?? $this->id),
        );
    }

    public function showTime()
    {
        return $this->belongsTo(ShowTime::class);
    }

    public function show()
    {
        return $this->belongsTo(Show::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
