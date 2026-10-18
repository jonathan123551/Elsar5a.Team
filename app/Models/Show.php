<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Show extends Model
{
    protected $fillable = [
        'title',
        'description',
        'poster',
        'poster_path',
        'price',
        'is_active',
        'ticket_template_path',
        'ticket_qr_x',
        'ticket_qr_y',
        'ticket_qr_size',
        'poster_public_id',
        'ticket_template_public_id',
    ];

    public function showTimes()
    {
        return $this->hasMany(ShowTime::class, 'show_id');
    }

    public function isPublicPlaceholder(): bool
    {
        return preg_match('/^(?:test|demo|sample|placeholder)(?:\b|[-_ ]|$)/i', trim((string) $this->title)) === 1;
    }

    public function getPosterAttribute(): ?string
    {
        return $this->poster_path;
    }

    public function setPosterAttribute($value): void
    {
        $this->attributes['poster_path'] = $value;
    }

    public function getEffectivePriceAttribute(): ?float
    {
        $min = $this->showTimes->min('ticket_price');
        return $min !== null ? (float) $min : null;
    }

    public function getPriceAttribute(): ?float
    {
        return $this->effective_price;
    }

    public function setPriceAttribute($value): void
    {
        // Allowed for compatibility when creating mock models in tests
    }
}
