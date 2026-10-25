<?php

namespace App\Services;

use App\Models\AdminNotification;
use App\Models\AdminNotificationPreference;
use App\Models\Booking;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AdminNotificationService
{
    public function __construct(protected WebPushService $webPush) {}

    /**
     * 1. New Booking created by customer.
     */
    public function notifyNewBooking(Booking $booking): void
    {
        $this->dispatchToAdmins([
            'type' => 'new_booking',
            'category' => 'new_bookings',
            'title' => "🔔 حجز جديد #{$booking->id}",
            'body' => 'تم إنشاء حجز جديد ويحتاج إلى مراجعة.',
            'booking_id' => $booking->id,
            'ticket_id' => null,
            'target_url' => "/admin/bookings/{$booking->id}",
            'dedupe_key' => "booking:{$booking->id}:new_booking",
        ]);
    }

    /**
     * 2. Payment/transfer proof uploaded for booking.
     */
    public function notifyPaymentProofUploaded(Booking $booking): void
    {
        if (empty($booking->transfer_screenshot_path)) {
            return;
        }

        $proofHash = substr(sha1((string) $booking->transfer_screenshot_path), 0, 16);

        $this->dispatchToAdmins([
            'type' => 'payment_proof_uploaded',
            'category' => 'payment_proofs',
            'title' => '💳 إثبات تحويل جديد',
            'body' => "تم رفع إثبات تحويل للحجز #{$booking->id} ويحتاج إلى مراجعة.",
            'booking_id' => $booking->id,
            'ticket_id' => null,
            'target_url' => "/admin/bookings/{$booking->id}",
            'dedupe_key' => "booking:{$booking->id}:payment_proof:{$proofHash}",
        ]);
    }

    /**
     * 3. Booking approved by Admin.
     * Avoid pushing to the same Admin who just clicked Approve.
     */
    public function notifyBookingApproved(Booking $booking, ?int $actingUserId = null): void
    {
        $this->dispatchToAdmins([
            'type' => 'booking_approved',
            'category' => 'new_bookings',
            'title' => "✅ تم اعتماد الحجز #{$booking->id}",
            'body' => "تم اعتماد الحجز #{$booking->id} وبدء تجهيز التذاكر.",
            'booking_id' => $booking->id,
            'ticket_id' => null,
            'target_url' => "/admin/bookings/{$booking->id}",
            'dedupe_key' => "booking:{$booking->id}:booking_approved",
        ], $actingUserId);
    }

    /**
     * 4. WhatsApp Template failure.
     */
    public function notifyWhatsAppTemplateFailed(Booking $booking, ?string $reason = null): void
    {
        $safeReason = self::sanitizeReason($reason);
        $body = "فشل إرسال قالب الاستلام للحجز #{$booking->id}.";
        if ($safeReason !== '') {
            $body .= " {$safeReason}";
        }

        $wamidKey = $booking->template_message_id ?: 'init';

        $this->dispatchToAdmins([
            'type' => 'whatsapp_template_failed',
            'category' => 'whatsapp_failures',
            'title' => '⚠️ فشل إرسال WhatsApp',
            'body' => $body,
            'booking_id' => $booking->id,
            'ticket_id' => null,
            'target_url' => "/admin/bookings/{$booking->id}",
            'dedupe_key' => "booking:{$booking->id}:template_failed:{$wamidKey}",
        ]);
    }

    /**
     * 5. Customer clicks "استلام التذكرة" CTA button on WhatsApp.
     */
    public function notifyCustomerClaimedTicket(Booking $booking): void
    {
        $wamidKey = $booking->template_message_id ?: 'init';

        $this->dispatchToAdmins([
            'type' => 'ticket_claimed',
            'category' => 'ticket_delivery',
            'title' => '📲 العميل طلب استلام التذكرة',
            'body' => "الحجز #{$booking->id} بدأ إرسال التذاكر.",
            'booking_id' => $booking->id,
            'ticket_id' => null,
            'target_url' => "/admin/bookings/{$booking->id}",
            'dedupe_key' => "booking:{$booking->id}:ticket_claimed:{$wamidKey}",
        ]);
    }

    /**
     * 6. Ticket QR image delivery failure.
     */
    public function notifyTicketDeliveryFailed(Ticket $ticket, ?string $reason = null): void
    {
        if (! $ticket->booking_id) {
            return;
        }

        $safeReason = self::sanitizeReason($reason);
        $body = "فشل تسليم إحدى تذاكر الحجز #{$ticket->booking_id}.";
        if ($safeReason !== '') {
            $body .= " {$safeReason}";
        }

        $wamidKey = $ticket->whatsapp_message_id ?: 'init';

        $this->dispatchToAdmins([
            'type' => 'ticket_delivery_failed',
            'category' => 'whatsapp_failures',
            'title' => '❌ فشل تسليم التذكرة',
            'body' => $body,
            'booking_id' => $ticket->booking_id,
            'ticket_id' => $ticket->id,
            'target_url' => "/admin/bookings/{$ticket->booking_id}",
            'dedupe_key' => "booking:{$ticket->booking_id}:ticket_failed:{$ticket->id}:{$wamidKey}",
        ]);
    }

    /**
     * 7 & 8. Ticket confirmed delivered (or read) by Meta webhook + Booking Completed check.
     * Must ONLY be called when a ticket transitions into ['delivered', 'read'].
     */
    public function notifyTicketDelivered(Ticket $ticket): void
    {
        if (! $ticket->booking_id) {
            return;
        }

        $booking = Booking::with('tickets')->find($ticket->booking_id);
        if (! $booking) {
            return;
        }

        $totalTickets = $booking->ticketsTotalCount();
        $deliveredTickets = $booking->ticketsDeliveredCount();
        $allDelivered = $totalTickets > 0 && $deliveredTickets >= $totalTickets;

        if ($allDelivered) {
            $this->dispatchToAdmins([
                'type' => 'ticket_delivered',
                'category' => 'ticket_delivery',
                'title' => '🎟️ تم تسليم التذاكر',
                'body' => "تم تسليم جميع تذاكر الحجز #{$booking->id}.",
                'booking_id' => $booking->id,
                'ticket_id' => $ticket->id,
                'target_url' => "/admin/bookings/{$booking->id}",
                'dedupe_key' => "booking:{$booking->id}:ticket_delivered:all",
            ]);

            $this->dispatchToAdmins([
                'type' => 'booking_completed',
                'category' => 'completed_bookings',
                'title' => "✅ الحجز #{$booking->id} مكتمل",
                'body' => 'تم إرسال واستلام جميع تذاكر الحجز بنجاح.',
                'booking_id' => $booking->id,
                'ticket_id' => null,
                'target_url' => "/admin/bookings/{$booking->id}",
                'dedupe_key' => "booking:{$booking->id}:booking_completed",
            ]);
        } else {
            $this->dispatchToAdmins([
                'type' => 'ticket_delivered',
                'category' => 'ticket_delivery',
                'title' => '🎟️ تم تسليم التذاكر',
                'body' => "تم تسليم تذكرة من الحجز #{$booking->id} ({$deliveredTickets}/{$totalTickets}).",
                'booking_id' => $booking->id,
                'ticket_id' => $ticket->id,
                'target_url' => "/admin/bookings/{$booking->id}",
                'dedupe_key' => "booking:{$booking->id}:ticket_delivered:ticket:{$ticket->id}",
            ]);
        }
    }

    /**
     * Persist in-app notification and dispatch Web Push to subscribed Admin devices.
     */
    protected function dispatchToAdmins(array $data, ?int $excludePushUserId = null): void
    {
        if (! Schema::hasTable('admin_notifications') || ! Schema::hasTable('users')) {
            return;
        }

        $admins = $this->resolveAdminUsers();
        if ($admins->isEmpty()) {
            return;
        }

        $targetUrl = AdminNotification::sanitizeTargetUrl(
            $data['target_url'] ?? null,
            isset($data['booking_id']) ? (int) $data['booking_id'] : null
        );

        foreach ($admins as $admin) {
            try {
                if (Schema::hasTable('admin_notification_preferences')) {
                    $pref = AdminNotificationPreference::forUser($admin->id);
                    if (! $pref->isCategoryEnabled((string) $data['category'])) {
                        continue;
                    }
                }

                $dedupeKey = $data['dedupe_key'] ?? null;
                if ($dedupeKey && AdminNotification::where('user_id', $admin->id)->where('dedupe_key', $dedupeKey)->exists()) {
                    continue;
                }

                $notification = AdminNotification::create([
                    'user_id' => $admin->id,
                    'type' => (string) $data['type'],
                    'category' => (string) $data['category'],
                    'title' => (string) $data['title'],
                    'body' => (string) $data['body'],
                    'booking_id' => $data['booking_id'] ?? null,
                    'ticket_id' => $data['ticket_id'] ?? null,
                    'target_url' => $targetUrl,
                    'dedupe_key' => $dedupeKey,
                ]);

                if ($excludePushUserId !== null && (int) $admin->id === (int) $excludePushUserId) {
                    continue;
                }

                // Only send Web Push notifications to the Admin device for new bookings,
                // exactly as requested by the admin: "مش محتاج نوتفيكيشنز تانى غير الحجوزات الجديده"
                $shouldSendWebPush = ($data['type'] === 'new_booking');

                if ($shouldSendWebPush) {
                    $unreadCount = AdminNotification::where('user_id', $admin->id)
                        ->whereNull('read_at')
                        ->count();

                    $sentCount = $this->webPush->sendToUser($admin->id, [
                        'notification_id' => $notification->id,
                        'type' => $notification->type,
                        'title' => $notification->title,
                        'body' => $notification->body,
                        'url' => $notification->target_url,
                        'booking_id' => $notification->booking_id,
                        'ticket_id' => $notification->ticket_id,
                        'icon' => '/images/pwa/admin-icon-192.png',
                        'badge' => '/images/pwa/admin-badge-96.png',
                        'unread_count' => $unreadCount,
                        'tag' => $dedupeKey ?: ('elsar5a-notif-' . $notification->id),
                        'timestamp' => $notification->created_at?->getTimestampMs() ?? (time() * 1000),
                    ]);

                    if ($sentCount > 0) {
                        $notification->update(['push_sent_at' => now()]);
                    }
                }
            } catch (\Throwable $e) {
                // Ignore unique constraint race on dedupe_key or non-fatal push errors
                Log::debug('ADMIN_NOTIFICATION_DISPATCH_SKIPPED', [
                    'admin_id' => $admin->id,
                    'type' => $data['type'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Find all authorized Admin users.
     *
     * @return Collection<int, User>
     */
    protected function resolveAdminUsers(): Collection
    {
        return User::where('email', 'elsar5ateam2026@gmail.com')->get();
    }

    /**
     * Strip sensitive tokens, URLs, or overly long stack traces from failure reasons.
     */
    public static function sanitizeReason(?string $reason): string
    {
        if (! is_string($reason) || trim($reason) === '') {
            return '';
        }

        $clean = trim($reason);

        // Redact anything resembling tokens, secrets, or URLs with query strings
        $clean = (string) preg_replace('/(Bearer\s+[A-Za-z0-9\-._~+\/]+=*|EAA[A-Za-z0-9]+)/i', '[REDACTED]', $clean);
        $clean = (string) preg_replace('/https?:\/\/\S+/i', '', $clean);
        $clean = trim((string) preg_replace('/\s+/', ' ', $clean));

        if (mb_strlen($clean) > 120) {
            $clean = mb_substr($clean, 0, 120) . '…';
        }

        return $clean;
    }
}
