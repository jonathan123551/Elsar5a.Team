<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'endpoint',
        'endpoint_hash',
        'public_key',
        'auth_token',
        'content_encoding',
        'user_agent',
        'device_label',
        'last_used_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $subscription): void {
            if ($subscription->endpoint && empty($subscription->endpoint_hash)) {
                $subscription->endpoint_hash = hash('sha256', trim((string) $subscription->endpoint));
            }
            if (empty($subscription->content_encoding)) {
                $subscription->content_encoding = 'aes128gcm';
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Upsert a browser/device Web Push subscription for an authenticated Admin user.
     * Supports multiple devices per Admin user while deduplicating identical endpoints.
     */
    public static function registerForUser(int $userId, array $attributes): self
    {
        $endpoint = trim((string) ($attributes['endpoint'] ?? ''));
        $endpointHash = hash('sha256', $endpoint);

        return static::updateOrCreate(
            ['endpoint_hash' => $endpointHash],
            [
                'user_id' => $userId,
                'endpoint' => $endpoint,
                'public_key' => (string) ($attributes['public_key'] ?? ''),
                'auth_token' => (string) ($attributes['auth_token'] ?? ''),
                'content_encoding' => (string) ($attributes['content_encoding'] ?? 'aes128gcm'),
                'user_agent' => isset($attributes['user_agent']) ? mb_substr((string) $attributes['user_agent'], 0, 500) : null,
                'device_label' => isset($attributes['device_label']) ? mb_substr((string) $attributes['device_label'], 0, 120) : null,
                'last_used_at' => now(),
            ]
        );
    }
}
