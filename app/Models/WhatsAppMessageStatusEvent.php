<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WhatsAppMessageStatusEvent extends Model
{
    protected $fillable = ['message_id', 'status', 'error_code', 'error_message', 'provider_timestamp'];

    protected $casts = ['provider_timestamp' => 'datetime'];

    private const RANK = ['sent' => 1, 'failed' => 2, 'undelivered' => 2, 'delivered' => 3, 'read' => 4];

    /** Keep an unmatched provider event until its outbound WAMID is persisted locally. */
    public static function record(string $messageId, string $status, ?int $errorCode, ?string $errorMessage, $providerTimestamp = null): void
    {
        if (! Schema::hasTable('whatsapp_message_status_events') || ! isset(self::RANK[$status])) {
            return;
        }

        DB::transaction(function () use ($messageId, $status, $errorCode, $errorMessage, $providerTimestamp) {
            $now = now();
            DB::table('whatsapp_message_status_events')->insertOrIgnore([
                'message_id' => $messageId,
                'status' => $status,
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
                'provider_timestamp' => $providerTimestamp ? date('Y-m-d H:i:s', (int) $providerTimestamp) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $existing = DB::table('whatsapp_message_status_events')->where('message_id', $messageId)->lockForUpdate()->first();
            if ($existing && (self::RANK[$status] ?? 0) > (self::RANK[$existing->status] ?? 0)) {
                DB::table('whatsapp_message_status_events')->where('id', $existing->id)->update([
                    'status' => $status,
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage,
                    'provider_timestamp' => $providerTimestamp ? date('Y-m-d H:i:s', (int) $providerTimestamp) : null,
                    'updated_at' => $now,
                ]);
            }

            DB::table('whatsapp_message_status_events')->where('created_at', '<', now()->subDays(14))->delete();
        });
    }

    /** Apply a queued-before-correlation callback after the response WAMID is saved. */
    public static function applyPendingForMessage(?string $messageId): void
    {
        if (! $messageId || ! Schema::hasTable('whatsapp_message_status_events')) {
            return;
        }

        $event = DB::table('whatsapp_message_status_events')->where('message_id', $messageId)->first();
        if (! $event) {
            return;
        }

        $ticket = Ticket::where('whatsapp_message_id', $messageId)->first();
        if ($ticket) {
            self::applyToTicket($ticket, $event);
            DB::table('whatsapp_message_status_events')->where('id', $event->id)->delete();
            return;
        }

        $booking = Booking::where('template_message_id', $messageId)->first();
        if ($booking) {
            self::applyToBooking($booking, $event);
            DB::table('whatsapp_message_status_events')->where('id', $event->id)->delete();
        }
    }

    private static function applyToTicket(Ticket $ticket, object $event): void
    {
        $now = now();
        if (in_array($event->status, ['delivered', 'read'], true)) {
            $wasAlreadyDelivered = in_array($ticket->delivery_status, ['delivered', 'read'], true);
            Ticket::whereKey($ticket->id)->update([
                'delivery_status' => $event->status === 'read' ? 'read' : DB::raw("CASE WHEN delivery_status = 'read' THEN 'read' ELSE 'delivered' END"),
                'whatsapp_sent' => true,
                'last_delivered_at' => $ticket->last_delivered_at ?? $now,
                'delivery_error' => null,
                'updated_at' => $now,
            ]);
            if ($ticket->booking_id) {
                Booking::whereKey($ticket->booking_id)->update(['updated_at' => $now]);
            }
            if (! $wasAlreadyDelivered) {
                app(\App\Services\AdminNotificationService::class)->notifyTicketDelivered($ticket->fresh());
            }
        } elseif ($event->status === 'sent') {
            Ticket::whereKey($ticket->id)->whereNotIn('delivery_status', ['delivered', 'read', 'failed'])->update([
                'delivery_status' => 'sent', 'whatsapp_sent' => true, 'updated_at' => $now,
            ]);
            if ($ticket->booking_id) {
                Booking::whereKey($ticket->booking_id)->update(['updated_at' => $now]);
            }
        } elseif (in_array($event->status, ['failed', 'undelivered'], true)) {
            $isWindowClosed = (int) ($event->error_code ?? 0) === 131047
                || str_contains((string) ($event->error_message ?? ''), '131047')
                || str_contains((string) ($event->error_message ?? ''), '24 ساعة');

            if ($isWindowClosed) {
                $ticket->markAwaitingCustomerClick(null, true);
                $phoneKey = preg_replace('/[^0-9]/', '', (string) $ticket->phone);
                $dedupeKey = 'wa_131047_tpl:' . ($ticket->booking_id ?? 0) . ':' . $phoneKey;

                if (\Illuminate\Support\Facades\Cache::add($dedupeKey, 1, now()->addSeconds(30))) {
                    $delivery = app(\App\Services\TicketDeliveryService::class);
                    $tplResponse = $delivery->sendTemplate($ticket->phone);
                    $tplWamid = $delivery->getMessageId($tplResponse);

                    if ($tplResponse->successful() && ! $tplResponse->json('error')) {
                        $ticket->booking?->markTemplateAccepted($tplWamid);
                    } else {
                        $tplError = $delivery->getErrorMessage($tplResponse);
                        $ticket->markWhatsAppFailed('نافذة الـ 24 ساعة مغلقة وفشل إرسال القالب أيضاً: ' . $tplError, true);
                        $ticket->booking?->markTemplateFailed($tplError);
                    }
                }
            } else {
                $ticket->markWhatsAppFailed($event->error_message ?: 'تعذر تسليم الرسالة عبر WhatsApp');
            }
        }
    }

    private static function applyToBooking(Booking $booking, object $event): void
    {
        if (in_array($event->status, ['delivered', 'read'], true)) {
            Booking::whereKey($booking->id)->update([
                'template_status' => $event->status === 'read' ? 'read' : DB::raw("CASE WHEN template_status = 'read' THEN 'read' ELSE 'delivered' END"),
                'template_error' => null,
                'updated_at' => now(),
            ]);
        } elseif ($event->status === 'sent') {
            Booking::whereKey($booking->id)->whereNotIn('template_status', ['delivered', 'read', 'failed'])->update([
                'template_status' => 'sent', 'updated_at' => now(),
            ]);
        } elseif (in_array($event->status, ['failed', 'undelivered'], true)) {
            $booking->markTemplateFailed($event->error_message ?: 'تعذر تسليم قالب WhatsApp');
        }
    }
}
