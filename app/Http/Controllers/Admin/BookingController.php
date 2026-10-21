<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateTicketImageJob;
use App\Jobs\SendWhatsAppTicketImageJob;
use App\Jobs\SendWhatsAppTicketTemplateJob;
use App\Models\Booking;
use App\Models\ShowTime;
use App\Models\Ticket;
use App\Services\TicketDeliveryService;
use App\Services\TicketRenderer;
use App\Support\CloudinaryUrl;
use App\Support\LogContext;
use Cloudinary\Configuration\Configuration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BookingController extends Controller
{
    public function __construct()
    {
        Configuration::instance([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key'    => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
            'url' => ['secure' => true],
        ]);
    }

    public function index(Request $request)
    {
        $bookings = Booking::with(['showTime.show', 'tickets'])
            ->latest()
            ->get();

        $unreceivedTicketsCount = Ticket::whereHas('booking', function ($q) {
            $q->where('status', 'approved');
        })
        ->where('whatsapp_sent', false)
        ->where(function ($q) {
            $q->whereNull('delivery_status')
              ->orWhere('delivery_status', '!=', 'sent');
        })
        ->count();

        return view('admin.bookings.index', compact('bookings', 'unreceivedTicketsCount'));
    }

    public function show(Booking $booking)
    {
        $booking->load('showTime.show');
        return view('admin.bookings.show', compact('booking'));
    }

    /**
     * Lightweight polling endpoint for live status updates in admin UI.
     * Accessible by authenticated admins via /admin/bookings/poll-status.
     */
    public function pollStatus(Request $request)
    {
        $unreceivedTicketsCount = Ticket::whereHas('booking', fn ($q) => $q->where('status', 'approved'))
            ->where('whatsapp_sent', false)
            ->where(function ($q) {
                $q->whereNull('delivery_status')
                  ->orWhere('delivery_status', '!=', 'delivered');
            })
            ->count();

        $counts = [
            'total_bookings'           => Booking::count(),
            'pending_bookings'         => Booking::where('status', 'pending')->count(),
            'approved_bookings'        => Booking::where('status', 'approved')->count(),
            'rejected_bookings'        => Booking::where('status', 'rejected')->count(),
            'unreceived_tickets_count' => $unreceivedTicketsCount,
            'latest_booking_id'        => (int) Booking::max('id'),
        ];

        $bookingData = null;
        $bookingId = $request->query('booking_id');

        if ($bookingId) {
            $booking = Booking::with(['tickets', 'showTime.show'])->find($bookingId);
            if ($booking) {
                $totalTickets = $booking->ticketsTotalCount();
                $deliveredTickets = $booking->ticketsDeliveredCount();
                $sentTickets = $booking->ticketsSentCount();
                $sendingTickets = $booking->ticketsSendingCount();
                $failedTickets = $booking->ticketsFailedCount();
                $claimedTickets = $booking->tickets->filter(fn($t) => !is_null($t->claimed_at))->count();
                $isTemplateFailed = $booking->template_status === 'failed';
                $isAllDelivered = ($deliveredTickets === $totalTickets) && ($totalTickets > 0);
                $awaitingClick = $booking->isAwaitingCustomerClick();

                $statusBadgeText = 'معتمد (لم يُرسل)';
                $statusBadgeType = 'gray';

                if ($isAllDelivered) {
                    $statusBadgeText = "تم تسليم جميع التذاكر بالكامل ({$deliveredTickets}/{$totalTickets})";
                    $statusBadgeType = 'emerald';
                } elseif ($isTemplateFailed) {
                    $statusBadgeText = 'فشل إرسال قالب استلام التذاكر';
                    $statusBadgeType = 'rose';
                } elseif ($failedTickets > 0) {
                    $statusBadgeText = "تعذر تسليم {$failedTickets} تذكرة ({$deliveredTickets}/{$totalTickets})";
                    $statusBadgeType = 'rose';
                } elseif ($awaitingClick) {
                    $statusBadgeText = 'أُرسل القالب · بانتظار نقر العميل على واتساب';
                    $statusBadgeType = 'sky';
                } elseif ($sendingTickets > 0 || $booking->template_status === 'sending') {
                    $statusBadgeText = 'جاري الإرسال لـ Meta';
                    $statusBadgeType = 'amber';
                } elseif ($sentTickets > 0) {
                    $statusBadgeText = "أُرسلت التذاكر للشبكة ({$sentTickets}/{$totalTickets})";
                    $statusBadgeType = 'teal';
                } elseif ($booking->status === 'approved') {
                    $statusBadgeText = 'معتمد (لم يُرسل)';
                    $statusBadgeType = 'gray';
                }

                $bookingData = [
                    'id'               => $booking->id,
                    'status'           => $booking->status,
                    'template_status'  => $booking->template_status,
                    'template_error'   => $booking->template_error,
                    'template_sent_at' => $booking->template_sent_at?->format('g:i A'),
                    'tickets'          => $booking->tickets->map(function ($ticket) {
                        return [
                            'id'              => $ticket->id,
                            'ticket_num'      => $ticket->ticket_num,
                            'name'            => $ticket->name,
                            'phone'           => $ticket->phone,
                            'delivery_status' => $ticket->delivery_status,
                            'whatsapp_sent'   => (bool) $ticket->whatsapp_sent,
                            'is_received'     => $ticket->isTicketDeliveredOrReceived(),
                            'delivery_error'  => $ticket->delivery_error,
                            'claimed_at'      => $ticket->claimed_at?->format('g:i A'),
                            'last_delivered'  => $ticket->last_delivered_at?->format('g:i A'),
                        ];
                    }),
                    'metrics' => [
                        'total_count'          => $totalTickets,
                        'delivered_count'      => $deliveredTickets,
                        'sent_count'           => $sentTickets,
                        'sending_count'        => $sendingTickets,
                        'failed_count'         => $failedTickets,
                        'claimed_count'        => $claimedTickets,
                        'awaiting_click'       => $awaitingClick,
                        'is_all_delivered'     => $isAllDelivered,
                        'is_template_failed'   => $isTemplateFailed,
                        'status_badge_text'    => $statusBadgeText,
                        'status_badge_type'    => $statusBadgeType,
                    ],
                ];
            }
        }

        return response()->json([
            'ok'      => true,
            'summary' => $counts,
            'booking' => $bookingData,
        ]);
    }

    /* =======================
     |  APPROVE BOOKING
     ======================= */
    public function approve(Booking $booking)
{
    if ($booking->status === 'approved') {
        return back()->with('status', 'الحجز معتمد بالفعل');
    }

    $booking->load('showTime.show', 'tickets');

    $show = $booking->showTime?->show;

    if (!$show || !$show->ticket_template_path) {
        return back()->with('status', 'لا يوجد قالب تذكرة لهذا العرض');
    }

    $booking->update([
        'status'          => 'approved',
        'approved_at'     => now(),
        'template_status' => 'sending',
        'template_error'  => null,
    ]);

    Log::info('BOOKING_APPROVED', LogContext::base(
        'BOOKING_APPROVED',
        'admin.booking.approve',
        [
            'booking_id' => $booking->id,
            'tickets_count' => (int) $booking->tickets->count(),
            'show_time_id' => $booking->show_time_id,
        ]
    ));

    // Build each ticket's QR image ahead of time (idempotent on
    // qr_image_path) so it's ready in Cloudinary by the time the customer
    // taps "أستلام التذكرة". The heavy GD + Cloudinary work no longer blocks
    // the admin's request — it runs in GenerateTicketImageJob.
    foreach ($booking->tickets as $ticket) {
        GenerateTicketImageJob::dispatch($ticket->id)->onQueue('high');
    }

    // ONE template per UNIQUE phone — not one per ticket. A 50-ticket
    // booking on the same number now sends a single "أستلام التذكرة"
    // template instead of 50 identical ones. The customer's single tap
    // then drains every remaining ticket for that phone (see
    // WhatsAppWebhookController::handle).
    $uniquePhones = $booking->tickets
        ->pluck('phone')
        ->map(fn ($phone) => preg_replace('/[^0-9]/', '', (string) $phone))
        ->filter()
        ->unique()
        ->values();

    foreach ($uniquePhones as $phone) {
        SendWhatsAppTicketTemplateJob::dispatch($phone, $booking->id)->onQueue('high');
    }

    return redirect()
        ->route('admin.bookings.show', $booking->id)
        ->with('status', 'تم اعتماد الحجز وإرسال رسالة الاستلام ✅');
}

    /* =======================
     | TEMPLATE
     ======================= */
public function sendTicketTemplate($phone)
{ 
    $phone = preg_replace('/[^0-9]/', '', $phone);

    Http::withToken(env('WHATSAPP_TOKEN'))->post(
        'https://graph.facebook.com/v23.0/' . env('WHATSAPP_PHONE_ID') . '/messages',
        [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'template',
            'template' => [
                'name' => 'ticket_clean_final',
                'language' => [
                    'code' => 'ar_EG'
                ],
                'components' => [] // 🔥 مهم جداً
            ]
        ]
    );
}

    /* =======================
     | SEND IMAGE
     ======================= */
    public function sendWhatsAppTicket($phone, $imageUrl, $reference, $full_name, $showTimeText)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Run the image through a WhatsApp-safe Cloudinary
        // transformation: forces JPEG, caps the long edge at
        // 1600 px, and picks an aggressive-but-clean quality.
        // This guarantees the link WhatsApp fetches is under
        // their ~5 MB silent-drop cap even if the underlying
        // ticket is huge.
        $imageUrl = CloudinaryUrl::forWhatsApp($imageUrl);

        $response = Http::withToken(env('WHATSAPP_TOKEN'))->post(
            'https://graph.facebook.com/v23.0/' . env('WHATSAPP_PHONE_ID') . '/messages',
            [
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'image',
                'image' => [
                    'link' => $imageUrl,
                    'caption' =>
                        "*🎟️ أهلاً {$full_name}*\n\n"
                            ."يسعدنا وجودك معنا،\n"
                            ."أنت الآن جزء من تجربة جديدة نصرخ فيها سويًا…\n\n"
                            ."ليزداد العقل وعيًا.\n\n"
                            ."نتمنى لك أمسية ثرية بالفن ✨\n\n"
                            ."نحن لا نطلب منك سوى حواسك،\n"
                            ."ولا ننتظر منك إلا أن تأتي إلى مصدر الصراخ…\n"
                            ."فهو دائمًا على المسرح 🎭\n\n"
                            ."نلتقي لنصرخ معًا،\n"
                            ."فنغيّر ما فسد،\n"
                            ."ونزرع بدلًا منه ثمرًا صالحًا ❤️\n\n"
                            ."🗓️ *موعد الحفلة:*\n"
                            ."{$showTimeText}\n\n"
                            ."‼️ *يرجى إحضار هذه التذكرة عند الدخول*",
            ],
            ]
        );

        if ($response->successful()) {
            Log::info('WHATSAPP_TICKET_SENT', LogContext::base(
                'WHATSAPP_TICKET_SENT',
                'admin.booking.send_ticket',
                [
                    'phone_masked' => LogContext::maskPhone($phone),
                    'http_status' => $response->status(),
                    'external_service' => 'meta_whatsapp',
                ]
            ));
        } else {
            Log::error('WHATSAPP_SEND_FAILED', LogContext::whatsappFailure(
                'admin.booking.send_ticket',
                $phone,
                $response,
                null
            ));
        }

        return $response;
    }

    /* =======================
     | WEBHOOK (استلام التذاكر)
     ======================= */
public function receiveTicket(Request $request)
{
    $phone = $request['from'];

    // تنظيف الرقم
    $phone = preg_replace('/[^0-9]/', '', $phone);

    Log::info('USER CLICKED', [
        'phone_masked' => LogContext::maskPhone($phone),
    ]);

    // هات كل التذاكر اللي لسه متبعتتش لنفس الرقم
    $tickets = Ticket::where('phone', $phone)
        ->where('whatsapp_sent', false)
        ->get();

    if ($tickets->isEmpty()) {
        return response()->json(['status' => 'no tickets']);
    }

    foreach ($tickets as $ticket) {

        if (!$ticket->qr_image_path) {
            continue;
        }

        $response = $this->sendWhatsAppTicket(
            $ticket->phone,
            $ticket->qr_image_path,
            $ticket->ticket_code,
            $ticket->name,
            ''
        );

        // Only mark as sent if WhatsApp actually accepted the
        // image. Previously every send was marked successful even
        // when the Cloud API silently dropped the image because
        // the file was too big — and the user's "resend" click
        // would no-op because the ticket was already flagged
        // sent.
        if ($response && $response->successful()) {
            $ticket->update([
                'whatsapp_sent' => true
            ]);
        }
    }

    return response()->json(['status' => 'sent']);
}
    /* =======================
     | REJECT
     ======================= */
    public function reject(Booking $booking)
    {
        $alreadyRejected = false;

        DB::transaction(function () use ($booking, &$alreadyRejected) {
            $booking = Booking::whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($booking->status === 'rejected') {
                $alreadyRejected = true;
                return;
            }

            $previousStatus = $booking->status;

            $ticketQuantity =
                $booking->tickets_count
                ?? $booking->quantity
                ?? $booking->tickets()->count();

            $showTime = ShowTime::whereKey($booking->show_time_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($previousStatus !== 'rejected') {
                $available = (int) $showTime->available_tickets;
                $total = $showTime->total_tickets !== null
                    ? (int) $showTime->total_tickets
                    : null;

                $restored = $available + (int) $ticketQuantity;

                if ($total !== null) {
                    $restored = min($restored, $total);
                }

                $showTime->available_tickets = $restored;

                if ($restored > 0) {
                    $showTime->is_sold_out = false;
                }

                $showTime->save();
            }

            $booking->status = 'rejected';

            if (Schema::hasColumn('bookings', 'rejected_at') || isset($booking->rejected_at)) {
                $booking->rejected_at = now();
            }

            $booking->save();

            Log::info('BOOKING_REJECTED', LogContext::base(
                'BOOKING_REJECTED',
                'admin.booking.reject',
                [
                    'booking_id' => $booking->id,
                    'previous_status' => $previousStatus,
                    'tickets_count' => (int) $ticketQuantity,
                    'show_time_id' => $showTime->id,
                ]
            ));
        });

        if ($alreadyRejected) {
            Log::warning('NON_FATAL_RECOVERY', LogContext::base(
                'NON_FATAL_RECOVERY',
                'admin.booking.reject.already_rejected',
                [
                    'booking_id' => $booking->id,
                    'reason' => 'booking_already_rejected',
                ]
            ));
        }

        return redirect()->back();
    }
    /* =======================
     | RESEND TICKET (Scenario A, B, C state machine)
     ======================= */
    public function resendTicket($id, TicketDeliveryService $delivery, TicketRenderer $renderer)
    {
        $ticket = Ticket::with(['booking.showTime.show'])->findOrFail($id);

        if (!$ticket->qr_image_path) {
            $url = $renderer->renderAndUpload($ticket);
            if (!$url) {
                return back()->with('error', '❌ تعذر تجهيز صورة التذكرة (تأكد من إعداد قالب التذاكر للعرض)');
            }
            $ticket->refresh();
        }

        $ticket->update(['resend_requested_at' => now()]);

        // Scenario A / C: Attempt direct image delivery
        $response = $delivery->sendImage($ticket);
        $wamid = $delivery->getMessageId($response);

        if ($response->successful() && !$response->json('error')) {
            $freshTicket = Ticket::where('id', $ticket->id)->first(['delivery_status', 'whatsapp_message_id', 'last_delivered_at']);
            $currentStatus = $freshTicket?->delivery_status;
            $nextStatus = in_array($currentStatus, ['delivered', 'read'], true) ? $currentStatus : 'sent';

            $ticket->update([
                'whatsapp_sent'        => true,
                'delivery_status'      => $nextStatus,
                'whatsapp_message_id'  => $wamid ?: ($freshTicket?->whatsapp_message_id ?: $ticket->whatsapp_message_id),
                'last_delivered_at'    => $freshTicket?->last_delivered_at ?? now(),
                'delivery_error'       => null,
            ]);

            return back()->with('status', '✅ تم إرسال صورة التذكرة بنجاح عبر واتساب مباشرة.');
        }

        // Scenario B: Meta 24-hour window is closed (Error 131047)
        if ($delivery->isWindowClosed($response)) {
            $tplResponse = $delivery->sendTemplate($ticket->phone);
            $tplWamid = $delivery->getMessageId($tplResponse);

            if ($tplResponse->successful() && !$tplResponse->json('error')) {
                $ticket->update([
                    'whatsapp_sent'   => false,
                    'delivery_status' => 'pending',
                    'delivery_error'  => 'نافذة الـ 24 ساعة مغلقة مع العميل. تم إرسال رسالة القالب لتجديد المحادثة.',
                ]);

                if ($ticket->booking) {
                    $ticket->booking->update([
                        'template_status'     => 'sent',
                        'template_message_id' => $tplWamid,
                        'template_sent_at'    => now(),
                        'template_error'      => null,
                    ]);
                }

                return back()->with('status', '⚠️ نافذة الـ 24 ساعة مغلقة مع العميل. تم إرسال قالب استلام التذكرة للعميل، وبمجرد نقره على "أستلام التذكرة" ستصله صورة التذكرة تلقائياً.');
            }

            $tplError = $delivery->getErrorMessage($tplResponse);
            $ticket->update([
                'whatsapp_sent'   => false,
                'delivery_status' => 'failed',
                'delivery_error'  => 'نافذة الـ 24 ساعة مغلقة وفشل إرسال القالب أيضاً: ' . $tplError,
            ]);

            if ($ticket->booking) {
                $ticket->booking->update([
                    'template_status' => 'failed',
                    'template_error'  => $tplError,
                ]);
            }

            return back()->with('error', '❌ نافذة الـ 24 ساعة مغلقة وفشل إرسال رسالة القالب: ' . $tplError);
        }

        // Other failure
        $errorMsg = $delivery->getErrorMessage($response);
        $ticket->update([
            'whatsapp_sent'   => false,
            'delivery_status' => 'failed',
            'delivery_error'  => $errorMsg,
        ]);

        return back()->with('error', '❌ فشل إرسال التذكرة عبر واتساب: ' . $errorMsg);
    }

    /* =======================
     | RESEND ALL TICKETS IN BOOKING
     ======================= */
    public function resendBookingTickets(Booking $booking, TicketDeliveryService $delivery, TicketRenderer $renderer)
    {
        $booking->loadMissing(['tickets', 'showTime.show']);

        if ($booking->tickets->isEmpty()) {
            return back()->with('error', 'لا توجد تذاكر مرتبطة بهذا الحجز');
        }

        $sentCount = 0;
        $windowClosed = false;
        $failCount = 0;

        foreach ($booking->tickets as $ticket) {
            if (!$ticket->qr_image_path) {
                $renderer->renderAndUpload($ticket);
                $ticket->refresh();
            }

            $ticket->update(['resend_requested_at' => now()]);
            $response = $delivery->sendImage($ticket);
            $wamid = $delivery->getMessageId($response);

            if ($response->successful() && !$response->json('error')) {
                $freshTicket = Ticket::where('id', $ticket->id)->first(['delivery_status', 'whatsapp_message_id', 'last_delivered_at']);
                $currentStatus = $freshTicket?->delivery_status;
                $nextStatus = in_array($currentStatus, ['delivered', 'read'], true) ? $currentStatus : 'sent';

                $ticket->update([
                    'whatsapp_sent'        => true,
                    'delivery_status'      => $nextStatus,
                    'whatsapp_message_id'  => $wamid ?: ($freshTicket?->whatsapp_message_id ?: $ticket->whatsapp_message_id),
                    'last_delivered_at'    => $freshTicket?->last_delivered_at ?? now(),
                    'delivery_error'       => null,
                ]);
                $sentCount++;
            } elseif ($delivery->isWindowClosed($response)) {
                $windowClosed = true;
                $ticket->update([
                    'whatsapp_sent'   => false,
                    'delivery_status' => 'pending',
                    'delivery_error'  => 'نافذة الـ 24 ساعة مغلقة مع العميل.',
                ]);
            } else {
                $failCount++;
                $ticket->update([
                    'whatsapp_sent'   => false,
                    'delivery_status' => 'failed',
                    'delivery_error'  => $delivery->getErrorMessage($response),
                ]);
            }
        }

        if ($windowClosed) {
            $uniquePhones = $booking->tickets
                ->pluck('phone')
                ->map(fn ($p) => preg_replace('/[^0-9]/', '', (string) $p))
                ->filter()
                ->unique();

            $tplSent = false;
            $tplErrorMsg = null;
            $lastTplWamid = null;

            foreach ($uniquePhones as $phone) {
                $tplRes = $delivery->sendTemplate($phone);
                if ($tplRes->successful() && !$tplRes->json('error')) {
                    $tplSent = true;
                    $lastTplWamid = $delivery->getMessageId($tplRes);
                } else {
                    $tplErrorMsg = $delivery->getErrorMessage($tplRes);
                }
            }

            if ($tplSent) {
                $booking->update([
                    'template_status'     => 'sent',
                    'template_message_id' => $lastTplWamid,
                    'template_sent_at'    => now(),
                    'template_error'      => null,
                ]);

                return back()->with('status', '⚠️ نافذة الـ 24 ساعة مغلقة مع العميل. تم إرسال قالب استلام التذاكر عبر واتساب بنجاح، وستصل التذاكر فور نقره على الزر.');
            }

            $booking->update([
                'template_status' => 'failed',
                'template_error'  => $tplErrorMsg,
            ]);

            return back()->with('error', '❌ نافذة الـ 24 ساعة مغلقة وفشل إرسال قالب استلام التذاكر: ' . $tplErrorMsg);
        }

        if ($sentCount > 0) {
            return back()->with('status', "✅ تم إرسال {$sentCount} تذكرة بنجاح عبر واتساب.");
        }

        return back()->with('error', '❌ فشل إرسال التذاكر عبر واتساب. يرجى مراجعة سجل الأخطاء.');
    }

    /* =======================
     | RESEND ONLY UNRECEIVED TICKETS IN BOOKING
     ======================= */
    public function resendUnreceivedTickets(Booking $booking, TicketDeliveryService $delivery, TicketRenderer $renderer)
    {
        $booking->loadMissing(['tickets', 'showTime.show']);

        $unreceivedTickets = $booking->tickets->filter(function ($t) {
            return ! $t->isTicketDeliveredOrReceived();
        });

        if ($unreceivedTickets->isEmpty()) {
            return back()->with('status', 'جميع تذاكر هذا الحجز مستلمة بالفعل ولا توجد أي تذاكر معلقة.');
        }

        $sentCount = 0;
        $templateCount = 0;
        $failCount = 0;

        foreach ($unreceivedTickets as $ticket) {
            if (!$ticket->qr_image_path) {
                $renderer->renderAndUpload($ticket);
                $ticket->refresh();
            }

            $ticket->update(['resend_requested_at' => now()]);
            $response = $delivery->sendImage($ticket);
            $wamid = $delivery->getMessageId($response);

            if ($response->successful() && !$response->json('error')) {
                $freshTicket = Ticket::where('id', $ticket->id)->first(['delivery_status', 'whatsapp_message_id', 'last_delivered_at']);
                $currentStatus = $freshTicket?->delivery_status;
                $nextStatus = in_array($currentStatus, ['delivered', 'read'], true) ? $currentStatus : 'sent';

                $ticket->update([
                    'whatsapp_sent'        => true,
                    'delivery_status'      => $nextStatus,
                    'whatsapp_message_id'  => $wamid ?: ($freshTicket?->whatsapp_message_id ?: $ticket->whatsapp_message_id),
                    'last_delivered_at'    => $freshTicket?->last_delivered_at ?? now(),
                    'delivery_error'       => null,
                ]);
                $sentCount++;
            } elseif ($delivery->isWindowClosed($response)) {
                $tplResponse = $delivery->sendTemplate($ticket->phone);
                $tplWamid = $delivery->getMessageId($tplResponse);
                if ($tplResponse->successful() && !$tplResponse->json('error')) {
                    $templateCount++;
                    $ticket->update([
                        'whatsapp_sent'   => false,
                        'delivery_status' => 'pending',
                        'delivery_error'  => 'نافذة الـ 24 ساعة مغلقة مع العميل (تم إرسال قالب استلام التذاكر للتجديد).',
                    ]);
                    $booking->update([
                        'template_status'     => 'sent',
                        'template_message_id' => $tplWamid,
                        'template_sent_at'    => now(),
                        'template_error'      => null,
                    ]);
                } else {
                    $failCount++;
                    $ticket->update([
                        'whatsapp_sent'   => false,
                        'delivery_status' => 'failed',
                        'delivery_error'  => 'نافذة الـ 24 ساعة مغلقة وفشل إرسال القالب أيضاً: ' . $delivery->getErrorMessage($tplResponse),
                    ]);
                }
            } else {
                $failCount++;
                $ticket->update([
                    'whatsapp_sent'   => false,
                    'delivery_status' => 'failed',
                    'delivery_error'  => $delivery->getErrorMessage($response),
                ]);
            }
        }

        if ($templateCount > 0) {
            $booking->update([
                'template_status'  => 'sent',
                'template_sent_at' => now(),
                'template_error'   => null,
            ]);
        }

        $feedback = "✓ {$sentCount} تم تسليمها مباشرة · ↻ {$templateCount} قوالب استلام أُرسلت · ⚠ {$failCount} تعذر إرسالها";

        if ($sentCount > 0 || $templateCount > 0) {
            return back()->with('status', $feedback);
        }

        return back()->with('error', $feedback);
    }

    /* ========================================================
     |  GLOBAL ACTION: SEND ALL UNRECEIVED TICKETS (ALL BOOKINGS)
     ======================================================== */
    public function sendAllUnreceivedTickets(TicketDeliveryService $delivery, TicketRenderer $renderer)
    {
        $unreceivedTickets = Ticket::with(['booking.showTime.show'])
            ->whereHas('booking', function ($q) {
                $q->where('status', 'approved');
            })
            ->where('whatsapp_sent', false)
            ->where(function ($q) {
                $q->whereNull('delivery_status')
                  ->orWhereNotIn('delivery_status', ['sent', 'delivered', 'read']);
            })
            ->get();

        $skippedCount = Ticket::whereHas('booking', function ($q) {
            $q->where('status', 'approved');
        })
        ->where(function ($q) {
            $q->where('whatsapp_sent', true)
              ->orWhereIn('delivery_status', ['sent', 'delivered', 'read']);
        })
        ->count();

        if ($unreceivedTickets->isEmpty()) {
            return redirect()
                ->route('admin.bookings.index')
                ->with('status', "جميع التذاكر مستلمة بالفعل ولا توجد أي تذاكر معلقة للإرسال (تم تخطي {$skippedCount} تذكرة مستلمة مسبقاً).");
        }

        $sentCount = 0;
        $templateCount = 0;
        $failCount = 0;
        $affectedBookings = [];

        foreach ($unreceivedTickets as $ticket) {
            // Guard: never duplicate-send if state changed concurrently
            if ($ticket->isTicketDeliveredOrReceived() || $ticket->whatsapp_sent || in_array($ticket->delivery_status, ['sent', 'delivered', 'read'], true)) {
                continue;
            }

            // Ensure QR image is rendered
            if (!$ticket->qr_image_path) {
                $renderer->renderAndUpload($ticket);
                $ticket->refresh();
            }

            $ticket->update(['resend_requested_at' => now()]);
            $response = $delivery->sendImage($ticket);
            $wamid = $delivery->getMessageId($response);

            if ($response->successful() && !$response->json('error')) {
                $freshTicket = Ticket::where('id', $ticket->id)->first(['delivery_status', 'whatsapp_message_id', 'last_delivered_at']);
                $currentStatus = $freshTicket?->delivery_status;
                $nextStatus = in_array($currentStatus, ['delivered', 'read'], true) ? $currentStatus : 'sent';

                $ticket->update([
                    'whatsapp_sent'        => true,
                    'delivery_status'      => $nextStatus,
                    'whatsapp_message_id'  => $wamid ?: ($freshTicket?->whatsapp_message_id ?: $ticket->whatsapp_message_id),
                    'last_delivered_at'    => $freshTicket?->last_delivered_at ?? now(),
                    'delivery_error'       => null,
                ]);
                $sentCount++;
            } elseif ($delivery->isWindowClosed($response)) {
                $tplResponse = $delivery->sendTemplate($ticket->phone);
                $tplWamid = $delivery->getMessageId($tplResponse);
                if ($tplResponse->successful() && !$tplResponse->json('error')) {
                    $templateCount++;
                    $ticket->update([
                        'whatsapp_sent'   => false,
                        'delivery_status' => 'pending',
                        'delivery_error'  => 'نافذة الـ 24 ساعة مغلقة مع العميل (تم إرسال قالب استلام التذاكر للتجديد).',
                    ]);
                    if ($ticket->booking_id) {
                        $affectedBookings[$ticket->booking_id] = $tplWamid;
                    }
                } else {
                    $failCount++;
                    $ticket->update([
                        'whatsapp_sent'   => false,
                        'delivery_status' => 'failed',
                        'delivery_error'  => 'نافذة الـ 24 ساعة مغلقة وفشل إرسال القالب أيضاً: ' . $delivery->getErrorMessage($tplResponse),
                    ]);
                }
            } else {
                $failCount++;
                $ticket->update([
                    'whatsapp_sent'   => false,
                    'delivery_status' => 'failed',
                    'delivery_error'  => $delivery->getErrorMessage($response),
                ]);
            }
        }

        foreach ($affectedBookings as $bId => $tplWamid) {
            Booking::where('id', $bId)->update([
                'template_status'     => 'sent',
                'template_message_id' => $tplWamid,
                'template_sent_at'    => now(),
                'template_error'      => null,
            ]);
        }

        $summary = "ملخص الإرسال الشامل: تم إرسال {$sentCount} تذكرة مباشرة · أُرسل {$templateCount} قالب استلام · تعذر إرسال {$failCount} · تم تخطي {$skippedCount} تذكرة مستلمة مسبقاً";

        if ($sentCount > 0 || $templateCount > 0) {
            return redirect()->route('admin.bookings.index')->with('status', $summary);
        }

        return redirect()->route('admin.bookings.index')->with('error', $summary);
    }
    public function delete($id)
{
    DB::transaction(function () use ($id) {
        $booking = Booking::whereKey($id)
            ->lockForUpdate()
            ->firstOrFail();

        $previousStatus = $booking->status;

        $ticketQuantity =
            $booking->tickets_count
            ?? $booking->quantity
            ?? $booking->tickets()->count();

        if ($previousStatus !== 'rejected') {
            $showTime = ShowTime::whereKey($booking->show_time_id)
                ->lockForUpdate()
                ->firstOrFail();

            $available = (int) $showTime->available_tickets;
            $total = $showTime->total_tickets !== null
                ? (int) $showTime->total_tickets
                : null;

            $restored = $available + (int) $ticketQuantity;

            if ($total !== null) {
                $restored = min($restored, $total);
            }

            $showTime->available_tickets = $restored;

            if ($restored > 0) {
                $showTime->is_sold_out = false;
            }

            $showTime->save();
        }

        $booking->tickets()->delete();
        $booking->delete();

        Log::info('BOOKING_DELETED', LogContext::base(
            'BOOKING_DELETED',
            'admin.booking.delete',
            [
                'booking_id' => $id,
                'previous_status' => $previousStatus,
                'tickets_count' => (int) $ticketQuantity,
            ]
        ));
    });

    return redirect()->back();
}

    /**
     * Public, unauthenticated landing for /ticket/{reference}.
     *
     * Historically the route mapped to a non-existent method, so any
     * hit returned an HTTP 500 (and, with APP_DEBUG=true, the full
     * Laravel ignition stack). The route is orphaned — nothing in the
     * UI links to it — so we keep it harmless by:
     *   - returning a small Arabic status page when the reference
     *     resolves to a real booking (no PII beyond what the booker
     *     already knows), and
     *   - aborting with a themed 404 when it doesn't.
     *
     * NOTE: this does NOT trigger any WhatsApp send. The WhatsApp
     * delivery flow is intentionally untouched.
     */
    public function sendTicketsByReference(string $reference)
    {
        $reference = trim($reference);

        $booking = Booking::with('showTime.show')
            ->where('reference_code', $reference)
            ->first();

        if (!$booking) {
            abort(404);
        }

        return view('site.ticket-status', compact('booking'));
    }
}
