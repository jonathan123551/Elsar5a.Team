<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShowTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'show_id',
        'play_id',
        'date',
        'time',
        'starts_at',
        'ticket_price',
        'price',
        'total_tickets',
        'capacity',
        'available_tickets',
        'is_sold_out',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'is_sold_out' => 'boolean',
    ];

    // العلاقة مع العرض نفسه
    public function show()
    {
        return $this->belongsTo(Show::class);
    }

    // Compatibility relationship for play
    public function play()
    {
        return $this->belongsTo(Show::class, 'show_id');
    }

    // الحجوزات المرتبطة بالميعاد
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'show_time_id');
    }

    public function getStartsAtAttribute(): ?\Carbon\Carbon
    {
        if (!$this->date) {
            return null;
        }
        $dateStr = $this->date instanceof \Carbon\CarbonInterface
            ? $this->date->format('Y-m-d')
            : (string) $this->date;
        $timeStr = $this->time ?: '00:00:00';
        return \Carbon\Carbon::parse("{$dateStr} {$timeStr}");
    }

    public function setStartsAtAttribute($value): void
    {
        if ($value) {
            $dt = $value instanceof \Carbon\CarbonInterface ? $value : \Carbon\Carbon::parse($value);
            $this->attributes['date'] = $dt->toDateString();
            $this->attributes['time'] = $dt->toTimeString();
        }
    }

    public function getPriceAttribute()
    {
        return $this->ticket_price;
    }

    public function setPriceAttribute($value): void
    {
        $this->attributes['ticket_price'] = $value;
    }

    public function getCapacityAttribute(): int
    {
        return (int) ($this->total_tickets ?? 0);
    }

    public function setCapacityAttribute($value): void
    {
        $this->attributes['total_tickets'] = $value;
        if (!isset($this->attributes['available_tickets'])) {
            $this->attributes['available_tickets'] = $value;
        }
    }

    public function getPlayIdAttribute()
    {
        return $this->show_id;
    }

    public function setPlayIdAttribute($value): void
    {
        $this->attributes['show_id'] = $value;
    }

    public function setStatusAttribute($value): void
    {
        if ($value === 'sold_out') {
            $this->attributes['is_sold_out'] = true;
            $this->attributes['available_tickets'] = 0;
        } elseif ($value === 'open') {
            $this->attributes['is_sold_out'] = false;
        }
    }

    public function isBookable(): bool
    {
        if ($this->is_sold_out || (int) $this->available_tickets <= 0) {
            return false;
        }
        if ($this->starts_at && $this->starts_at->isPast()) {
            return false;
        }
        return true;
    }

    public function remainingSeats(): int
    {
        if ($this->relationLoaded('bookings') || $this->bookings()->exists()) {
            $booked = (int) $this->bookings()
                ->whereIn('status', ['approved', 'confirmed'])
                ->sum('tickets_count');
            if ($booked > 0 && (int) $this->available_tickets === (int) $this->total_tickets) {
                return max(0, (int) $this->total_tickets - $booked);
            }
        }
        return max(0, (int) $this->available_tickets);
    }
}
