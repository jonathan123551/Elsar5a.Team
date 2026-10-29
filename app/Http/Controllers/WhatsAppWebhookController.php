<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\SendWhatsAppTicketImageJob;
use App\Models\Ticket;
use App\Models\Booking;
use App\Models\WhatsAppMessageStatusEvent;
use App\Models\WhatsAppTemplateMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Support\LogContext;
use App\Support\PhoneNumber;

class WhatsAppWebhookController extends Controller
{
    /* =======================
       VERIFY WEBHOOK
    ======================= */
    public function verify(Request $request)
    {
        if (
            $request->hub_mode === 'subscribe' &&
            $request->hub_verify_token === env('WHATSAPP_VERIFY_TOKEN')
        ) {
            return response($request->hub_challenge, 200);
        }

        return response('Forbidden', 403);
    }

    /* =======================
       HANDLE INCOMING
    ======================= */
    public function handle(Request $request)
    {
        try {
            // 1. Process asynchronous message status updates from Meta (sent, delivered, failed)
            $statuses = $request->input('entry.0.changes.0.value.statuses');
            if (is_array($statuses) && !empty($statuses)) {
                foreach ($statuses as $status) {
                    $this->handleStatusUpdate($status);
                }
                $this->forwardToChatwoot($request);
                return response()->json(['ok' => true]);
            }

            $message = $request->input('entry.0.changes.0.value.messages.0');

            if (!$message || !isset($message['from'])) {
                $this->forwardToChatwoot($request);
                return response()->json(['ok' => true]);
            }

            // 🔒 Idempotency: Meta retries a webhook delivery if we don't 2xx
            // fast enough, and a customer can double-tap the button. Dedupe on
            // the inbound message id so the same tap can't enqueue the same
            // tickets twice (and can't double-forward to Chatwoot).
            $messageId = $message['id'] ?? null;
            if ($messageId && !Cache::add('wa_in:' . $messageId, 1, now()->addMinutes(10))) {
                Log::warning('DUPLICATE_REQUEST_BLOCKED', LogContext::base(
                    'DUPLICATE_REQUEST_BLOCKED',
                    'webhook.whatsapp.dedupe',
                    ['message_id' => $messageId]
                ));
                return response()->json(['ok' => true, 'dedupe' => true]);
            }

            // 📱 Normalize phone
            $phone = preg_replace('/[^0-9]/', '', $message['from']);

            // 📝 Get message text. Read every shape Meta can deliver — the
            // payload/id sources matter because template buttons sometimes
            // return only the developer-defined id, not the visible label.
            $text = $message['text']['body']
                ?? $message['button']['text']
                ?? $message['button']['payload']
                ?? $message['interactive']['button_reply']['title']
                ?? $message['interactive']['button_reply']['id']
                ?? '';

            Log::info('INCOMING MESSAGE', [
                'phone_masked' => LogContext::maskPhone($phone),
                'text'  => $text,
                'type'  => $message['type'] ?? 'unknown',
            ]);

            /* ==========================
               🎟 SEND TICKET LOGIC
            ========================== */

            // Accept both Arabic spellings of "receive ticket" plus the canonical
            // button payload id. Meta returns whichever variant the template was
            // approved with — alef-hamza is the formal spelling, bare alef the
            // common one — so match both rather than one.
            $triggers = [
                'أستلام التذكرة',  // alef-hamza U+0623 (the approved template)
                'استلام التذكرة',  // bare alef  U+0627
                'receive_ticket',  // button payload id
            ];

            if (in_array(trim($text), $triggers, true)) {

                // 🎟 Drain ALL remaining undelivered tickets for this phone in
                // one tap — not just the first one. Previously the handler sent
                // a single ticket per tap, so a 50-ticket booking needed 50 taps
                // and any tickets the customer never tapped for were lost.
                //
                // Scope: only APPROVED bookings are eligible (a ticket's QR is
                // only built at approval). Each ticket gets its own queued job;
                // the job's atomic claim guarantees exactly-once delivery even if
                // this same tap arrives twice or collides with a resend.
                // When a customer presses "استلام التذكرة" / "Claim Ticket",
                // find ALL tickets belonging to this customer's phone number across
                // ALL approved bookings, and send every ticket that has not already
                // reached a confirmed delivered state.
                $normalizedSenderPhone = PhoneNumber::canonical($phone);
                $templateContextId = data_get($message, 'context.id');

                // 1. Verify CTA origin & legitimacy if context.id is present.
                if ($templateContextId) {
                    $dispatch = WhatsAppTemplateMessage::findByMessageId($templateContextId);
                    $contextBooking = $dispatch?->booking_id
                        ? Booking::with('tickets')->find($dispatch->booking_id)
                        : Booking::with('tickets')->where('template_message_id', $templateContextId)->first();

                    if ($dispatch || $contextBooking) {
                        $senderBelongs = false;
                        if ($dispatch && PhoneNumber::same($dispatch->phone, $normalizedSenderPhone)) {
                            $senderBelongs = true;
                        }
                        if ($contextBooking && (
                            PhoneNumber::same($contextBooking->phone, $normalizedSenderPhone)
                            || $contextBooking->tickets->contains(fn ($t) => PhoneNumber::same($t->phone, $normalizedSenderPhone))
                        )) {
                            $senderBelongs = true;
                        }

                        if (! $senderBelongs) {
                            Log::warning('WHATSAPP_CTA_CONTEXT_PHONE_MISMATCH', LogContext::base(
                                'WHATSAPP_CTA_CONTEXT_PHONE_MISMATCH',
                                'webhook.whatsapp.claim',
                                [
                                    'message_id' => $messageId,
                                    'context_message_id' => $templateContextId,
                                    'booking_id' => $contextBooking?->id ?? $dispatch?->booking_id,
                                    'phone_masked' => LogContext::maskPhone($phone),
                                ]
                            ));
                            $this->forwardToChatwoot($request);
                            return response()->json(['ok' => true, 'status' => 'context_phone_mismatch']);
                        }
                    } else {
                        Log::info('WHATSAPP_CTA_CONTEXT_UNMATCHED', LogContext::base(
                            'WHATSAPP_CTA_CONTEXT_UNMATCHED',
                            'webhook.whatsapp.claim',
                            [
                                'message_id' => $messageId,
                                'context_message_id' => $templateContextId,
                                'phone_masked' => LogContext::maskPhone($phone),
                            ]
                        ));
                    }
                }

                // 2. Find ALL eligible tickets belonging to this customer across ALL approved bookings.
                // - Exclude tickets confirmed delivered by provider ('delivered', 'read') -> NEVER resend
                // - Exclude tickets in-flight in a worker ('sending') -> do NOT create concurrent duplicate
                // - Keep fresh pending tickets not yet claimed
                // - Keep failed tickets -> eligible for retry according to state-machine rules
                // - Keep sent tickets (accepted by Meta but unconfirmed) if past the in-flight network transit window -> retry
                $inFlightCutoff = now()->subSeconds(15);

                $eligibleTickets = Ticket::whereHas('booking', function ($q) {
                        $q->where('status', 'approved');
                    })
                    ->whereNotIn('delivery_status', ['delivered', 'read'])
                    ->where('delivery_status', '!=', 'sending')
                    ->where(function ($q) use ($inFlightCutoff) {
                        // A. Fresh unclaimed pending
                        $q->where(function ($sub) {
                            $sub->whereNull('claimed_at')
                                ->where(function ($d) {
                                    $d->whereNull('delivery_status')->orWhere('delivery_status', 'pending');
                                });
                        })
                        // B. Failed tickets are eligible for retry
                        ->orWhere('delivery_status', 'failed')
                        // C. Sent (accepted by Meta) but unconfirmed: eligible for retry once past network transit window
                        ->orWhere(function ($sub) use ($inFlightCutoff) {
                            $sub->where('delivery_status', 'sent')
                                ->where(function ($t) use ($inFlightCutoff) {
                                    $t->whereNull('claimed_at')
                                      ->orWhere('claimed_at', '<', $inFlightCutoff);
                                });
                        });
                    })
                    ->orderBy('booking_id')
                    ->orderBy('id')
                    ->get()
                    ->filter(fn ($ticket) => PhoneNumber::same($ticket->phone, $normalizedSenderPhone));

                $ticketIds = $eligibleTickets->pluck('id')->values();

                if ($ticketIds->isEmpty()) {
                    Log::info('NO TICKET FOUND', ['phone_masked' => LogContext::maskPhone($phone)]);
                    $this->forwardToChatwoot($request);
                    return response()->json(['status' => 'no ticket']);
                }

                Log::info('QUEUEING TICKETS', [
                    'phone_masked' => LogContext::maskPhone($phone),
                    'count' => $ticketIds->count(),
                    'ticket_ids' => $ticketIds->all(),
                ]);

                // Record customer claimed timestamp and ready status atomically.
                // Setting delivery_status to 'pending' and whatsapp_sent to false
                // allows SendWhatsAppTicketImageJob's compare-and-set claim to lock and transmit.
                $now = now();
                Ticket::whereIn('id', $ticketIds)
                    ->whereNotIn('delivery_status', ['delivered', 'read', 'sending'])
                    ->update([
                        'claimed_at'      => $now,
                        'delivery_status' => 'pending',
                        'whatsapp_sent'   => false,
                        'delivery_error'  => null,
                        'updated_at'      => $now,
                    ]);

                $bookingIds = $eligibleTickets->pluck('booking_id')->unique()->filter();
                Booking::whereIn('id', $bookingIds)->update(['updated_at' => $now]);

                foreach ($bookingIds as $bId) {
                    $b = Booking::find($bId);
                    if ($b) {
                        app(\App\Services\AdminNotificationService::class)->notifyCustomerClaimedTicket($b);
                    }
                }

                foreach ($ticketIds as $ticketId) {
                    SendWhatsAppTicketImageJob::dispatch($ticketId)->onQueue('high');
                }
            }

            /* ==========================
               🔁 FORWARD TO CHATWOOT
            ========================== */

            $this->forwardToChatwoot($request);

            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            Log::error('WEBHOOK_PROCESSING_FAILED', LogContext::base(
                'WEBHOOK_PROCESSING_FAILED',
                'webhook.whatsapp.handle',
                [
                    'exception_class' => get_class($e),
                    'exception_message' => $e->getMessage(),
                ]
            ));
            throw $e;
        }
    }

    private function forwardToChatwoot(Request $request)
    {
        try {
            $chatwootWebhookUrl = env('CHATWOOT_WHATSAPP_WEBHOOK_URL');

            if (!$chatwootWebhookUrl) {
                return;
            }

            $response = Http::timeout(10)->post($chatwootWebhookUrl, $request->all());

            if (!$response->successful()) {
                Log::error('WEBHOOK_PROCESSING_FAILED', LogContext::base(
                    'WEBHOOK_PROCESSING_FAILED',
                    'webhook.forward_chatwoot',
                    [
                        'target' => 'chatwoot',
                        'http_status' => $response->status(),
                    ]
                ));
            }
        } catch (\Throwable $e) {
            Log::error('WEBHOOK_PROCESSING_FAILED', LogContext::base(
                'WEBHOOK_PROCESSING_FAILED',
                'webhook.forward_chatwoot',
                [
                    'target' => 'chatwoot',
                    'exception_class' => get_class($e),
                    'exception_message' => $e->getMessage(),
                ]
            ));
        }
    }

    /* ========================================================
       PROCESS ASYNCHRONOUS META STATUS UPDATE (sent/delivered/failed)
       ======================================================== */
    protected function handleStatusUpdate(array $status): void
    {
        $wamid = $status['id'] ?? null;
        $statusName = $status['status'] ?? null; // 'sent', 'delivered', 'read', 'failed', 'undelivered'
        $recipientId = (string) ($status['recipient_id'] ?? '');
        $phoneNormalized = preg_replace('/[^0-9]/', '', $recipientId);
        $errorCode = (int) data_get($status, 'errors.0.code');
        $rawErrorMsg = data_get($status, 'errors.0.message') ?? data_get($status, 'errors.0.title');
        $formattedError = \App\Services\TicketDeliveryService::formatMetaError($errorCode ?: null, $rawErrorMsg);

        if ($wamid && $statusName) {
            WhatsAppMessageStatusEvent::record($wamid, $statusName, $errorCode ?: null, $formattedError, $status['timestamp'] ?? null);
        }

        Log::info('WHATSAPP_STATUS_UPDATE', LogContext::base(
            'WHATSAPP_STATUS_UPDATE',
            'webhook.whatsapp.status',
            [
                'status'          => $statusName,
                'message_id'      => $wamid,
                'recipient_id'    => LogContext::maskPhone($recipientId),
                'meta_error_code' => $errorCode ?: null,
                'meta_error_raw'  => $rawErrorMsg,
                'meta_error_msg'  => $formattedError,
            ]
        ));

        // 1. Try to match a Ticket by message ID (exact correlation per ticket)
        $ticket = null;
        if ($wamid) {
            $ticket = Ticket::where('whatsapp_message_id', $wamid)->first();
        }

        if ($ticket) {
            $now = now();
            if (in_array($statusName, ['delivered', 'read'], true)) {
                $wasAlreadyDelivered = in_array($ticket->delivery_status, ['delivered', 'read'], true);
                Ticket::whereKey($ticket->id)->update([
                    'delivery_status'   => $statusName === 'read' ? 'read' : DB::raw("CASE WHEN delivery_status = 'read' THEN 'read' ELSE 'delivered' END"),
                    'whatsapp_sent'     => true,
                    'last_delivered_at' => $ticket->last_delivered_at ?? $now,
                    'delivery_error'    => null,
                    'updated_at'        => $now,
                ]);
                if ($ticket->booking_id) {
                    Booking::whereKey($ticket->booking_id)->update(['updated_at' => $now]);
                }
                if (! $wasAlreadyDelivered) {
                    app(\App\Services\AdminNotificationService::class)->notifyTicketDelivered($ticket->fresh());
                }
            } elseif ($statusName === 'sent') {
                Ticket::whereKey($ticket->id)->whereNotIn('delivery_status', ['delivered', 'read', 'failed'])->update([
                    'delivery_status' => 'sent', 'whatsapp_sent' => true, 'updated_at' => $now,
                ]);
                if ($ticket->booking_id) {
                    Booking::whereKey($ticket->booking_id)->update(['updated_at' => $now]);
                }
            } elseif (in_array($statusName, ['failed', 'undelivered'], true)) {
                $isWindowClosedError = $errorCode === 131047
                    || str_contains(strtolower((string) $rawErrorMsg), '131047')
                    || str_contains(strtolower((string) $rawErrorMsg), '24 hours')
                    || str_contains(strtolower((string) $rawErrorMsg), 're-engagement');

                if ($isWindowClosedError) {
                    $ticket->markAwaitingCustomerClick(null, true);
                    $phoneKey = preg_replace('/[^0-9]/', '', (string) $ticket->phone);
                    $dedupeKey = 'wa_131047_tpl:' . ($ticket->booking_id ?? 0) . ':' . $phoneKey;

                    if (Cache::add($dedupeKey, 1, now()->addSeconds(30))) {
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
                    $ticket->markWhatsAppFailed($formattedError);
                }
            }
            if ($wamid && Schema::hasTable('whatsapp_message_status_events')) DB::table('whatsapp_message_status_events')->where('message_id', $wamid)->delete();
            return;
        }

        // 2. Try to match a Booking template by message ID
        $booking = null;
        if ($wamid) {
            $booking = \App\Models\Booking::where('template_message_id', $wamid)->first();
            if (! $booking) {
                $dispatch = \App\Models\WhatsAppTemplateMessage::findByMessageId($wamid);
                if ($dispatch?->booking_id) {
                    $booking = \App\Models\Booking::find($dispatch->booking_id);
                }
            }
        }

        // Fallback matching by phone for booking template
        if (!$booking && !$wamid && $phoneNormalized) {
            $phoneSuffix = strlen($phoneNormalized) >= 9 ? substr($phoneNormalized, -9) : $phoneNormalized;
            $matches = Booking::where(function ($q) use ($phoneNormalized, $phoneSuffix) {
                $q->where('phone', $phoneNormalized)
                  ->orWhere('phone', 'like', '%' . $phoneSuffix);
            })
            ->whereIn('template_status', ['sending', 'sent', 'delivered', 'read'])
            ->get();
            if ($matches->count() === 1) {
                $booking = $matches->first();
            } elseif ($matches->count() > 1) {
                Log::warning('WHATSAPP_TEMPLATE_STATUS_AMBIGUOUS', LogContext::base(
                    'WHATSAPP_TEMPLATE_STATUS_AMBIGUOUS',
                    'webhook.whatsapp.status',
                    ['message_id' => $wamid, 'candidate_count' => $matches->count(), 'recipient_id' => LogContext::maskPhone($recipientId)]
                ));
            }
        }

        if ($booking) {
            if (in_array($statusName, ['delivered', 'read'], true)) {
                Booking::whereKey($booking->id)->update([
                    'template_status' => $statusName === 'read' ? 'read' : DB::raw("CASE WHEN template_status = 'read' THEN 'read' ELSE 'delivered' END"),
                    'template_error'  => null,
                    'updated_at'      => now(),
                ]);
            } elseif ($statusName === 'sent') {
                Booking::whereKey($booking->id)->whereNotIn('template_status', ['delivered', 'read', 'failed'])->update(['template_status' => 'sent', 'updated_at' => now()]);
            } elseif (in_array($statusName, ['failed', 'undelivered'], true)) {
                $booking->markTemplateFailed($formattedError);
            }
            if ($wamid && Schema::hasTable('whatsapp_message_status_events')) DB::table('whatsapp_message_status_events')->where('message_id', $wamid)->delete();
            return;
        }

        // 3. Status not matched to ticket or booking
        Log::info('WHATSAPP_UNMATCHED_STATUS', LogContext::base(
            'WHATSAPP_UNMATCHED_STATUS',
            'webhook.whatsapp.unmatched',
            [
                'status'       => $statusName,
                'message_id'   => $wamid,
                'recipient_id' => LogContext::maskPhone($recipientId),
            ]
        ));
    }
}
