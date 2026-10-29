<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Show;
use App\Models\ShowTime;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdminBookingsAndResendTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Show $show;
    private ShowTime $showTime;

    private function confirmTicketDelivery(Ticket $ticket): void
    {
        $ticket->refresh();
        $this->postJson('/webhook/whatsapp', [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'statuses' => [[
                            'id' => $ticket->whatsapp_message_id,
                            'status' => 'delivered',
                            'recipient_id' => preg_replace('/[^0-9]/', '', $ticket->phone),
                        ]],
                    ],
                ]],
            ]],
        ])->assertOk();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Admin user is seeded by 0001_01_01_000000_create_users_table migration
        $this->admin = User::firstWhere('email', 'elsar5ateam2026@gmail.com')
            ?? User::create([
                'name' => 'مدير النظام',
                'email' => 'elsar5ateam2026@gmail.com',
                'password' => bcrypt('password'),
            ]);

        $this->show = Show::create([
            'title' => 'عرض الصرخة الكبرى',
            'description' => 'وصف تجريبي للعرض المسرحي.',
            'is_active' => true,
            'ticket_price' => 150,
            'ticket_template_path' => 'https://res.cloudinary.com/demo/image/upload/template.png',
            'ticket_qr_x' => 100,
            'ticket_qr_y' => 100,
            'ticket_qr_size' => 200,
        ]);

        $this->showTime = ShowTime::create([
            'show_id' => $this->show->id,
            'date' => now()->addDays(5)->format('Y-m-d'),
            'time' => '19:00:00',
            'hall_name' => 'مسرح الفلكي',
            'ticket_price' => 150,
            'available_tickets' => 50,
            'total_tickets' => 50,
            'is_sold_out' => false,
        ]);
    }

    /**
     * Requirement 1 & 2: Dedicated admin layout is isolated from the public website layout.
     * The admin dashboard must render the dedicated admin brand and navigation,
     * and MUST NOT render the public header or public theatrical manifesto footer.
     */
    public function test_admin_layout_renders_operational_navbar_and_no_public_headers_or_footers()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);

        // Operational admin navigation brand & sections
        $response->assertSee('إدارة الصرخة');
        $response->assertSee('لوحة تحكم الأدمن');
        $response->assertSee('الحجوزات');
        $response->assertSee('العروض');
        $response->assertSee('الأرشيف');
        $response->assertSee('فحص التذاكر');
        $response->assertSee('الدفع');
        $response->assertSee('خروج');

        // MUST NOT contain public layout navigation or manifesto footer
        $response->assertDontSee('فريق الصرخة المسرحي — حركة مسرحية مستقلة');
        $response->assertDontSee('الصوت الصادق لما لا يقال');

        // Specification: Team applications link removed from visible navigation
        $response->assertDontSee('طلبات الانضمام للفريق');
        $response->assertDontSee('>الانضمام<', false);
    }

    /**
     * Requirement 3: Admin bookings index renders payment proof preview,
     * total EGP calculation, and multi-stage delivery status indicators.
     */
    public function test_admin_bookings_index_renders_payment_proof_total_egp_and_delivery_badges()
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'أحمد رضوان',
            'phone' => '01012345678',
            'tickets_count' => 2,
            'total_price' => 300,
            'status' => 'approved',
            'template_status' => 'sent',
            'template_sent_at' => now(),
            'transfer_screenshot_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/receipt.jpg',
            'reference_code' => 'SRX-TEST-001',
        ]);

        Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'أحمد رضوان (1)',
            'phone' => '01012345678',
            'ticket_code' => 'TICK-TEST-1',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/t1.jpg',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'أحمد رضوان (2)',
            'phone' => '01012345678',
            'ticket_code' => 'TICK-TEST-2',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/t2.jpg',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.bookings.index'));

        $response->assertStatus(200);

        // Customer and reference
        $response->assertSee('أحمد رضوان');
        $response->assertSee('SRX-TEST-001');

        // Total price formatted in EGP
        $response->assertSee('300');
        $response->assertSee('ج.م');

        // Proof thumbnail preview button and modal hook
        $response->assertSee('receipt.jpg');
        $response->assertSee('adm-screenshot-modal');

        // Multi-stage delivery indicators
        $response->assertSee('بانتظار نقر العميل');
        $response->assertSee('2 تذكرة');
    }

    /**
     * Requirement 4: Booking details show page renders delivery lifecycle timeline,
     * ticket stubs with resend action, floating action dock, and back button.
     */
    public function test_admin_booking_show_renders_lifecycle_timeline_and_ticket_stubs_and_back_button()
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'سارة محمود',
            'phone' => '01198765432',
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'sent',
            'template_sent_at' => now(),
            'transfer_screenshot_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/receipt2.jpg',
            'reference_code' => 'SRX-TEST-002',
        ]);

        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'سارة محمود',
            'phone' => '01198765432',
            'ticket_code' => 'TICK-SM-001',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/ticket_sarah.jpg',
            'whatsapp_sent' => false,
            'delivery_status' => 'failed',
            'delivery_error' => 'تعذر الوصول لرقم الواتساب',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.bookings.show', $booking));

        $response->assertStatus(200);

        // Standardized back button
        $response->assertSee('العودة لقائمة الحجوزات');

        // Refined Ticket Delivery Status section (replaces large timeline)
        $response->assertSee('حالة تسليم التذاكر عبر واتساب');
        $response->assertSee('تفاصيل التذاكر الفردية');
        $response->assertSee('صورة إثبات التحويل');

        // Ticket stub and error details
        $response->assertSee('TICK-SM-001');
        $response->assertSee('تعذر الوصول لرقم الواتساب');
        $response->assertSee('إعادة إرسال التذكرة');

        // Floating action dock contains only delete for approved bookings
        $response->assertSee('حذف الحجز');
    }

    /**
     * Requirement 5 — Scenario A: 24h Meta conversation window is open.
     * Admin initiates resend -> direct image delivery succeeds.
     */
    public function test_scenario_a_direct_ticket_resend_when_24h_window_open()
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'عمرو مصطفى',
            'phone' => '01234567890',
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'sent',
            'reference_code' => 'SRX-AMR-001',
        ]);

        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'عمرو مصطفى',
            'phone' => '01234567890',
            'ticket_code' => 'TICK-AMR-001',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/ticket_amr.jpg',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
            'claimed_at' => now(),
        ]);

        // Mock Meta WhatsApp Graph API: image send succeeds with HTTP 200
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'contacts' => [['input' => '01234567890', 'wa_id' => '201234567890']],
                'messages' => [['id' => 'wamid.HBgLMjAx...']],
            ], 200),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.resend.ticket', $ticket->id));

        $response->assertRedirect();
        $response->assertSessionHas('status', '✅ تم إرسال صورة التذكرة بنجاح عبر واتساب مباشرة.');

        $ticket->refresh();
        $this->assertTrue((bool) $ticket->whatsapp_sent);
        $this->assertEquals('sent', $ticket->delivery_status);
        $this->assertNull($ticket->last_delivered_at, 'HTTP acceptance is not Meta-confirmed delivery.');
        $this->assertFalse($ticket->isTicketDeliveredOrReceived());
        $this->assertNotNull($ticket->resend_requested_at);
        $this->assertNull($ticket->delivery_error);
    }


    public function test_resend_before_customer_click_resends_template_not_qr_image()
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'عميل لم ينقر',
            'phone' => '01234567890',
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'delivered',
            'template_message_id' => 'wamid.template_original',
            'reference_code' => 'SRX-NO-CLICK-001',
        ]);
        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'عميل لم ينقر',
            'phone' => '01234567890',
            'ticket_code' => 'TICK-NO-CLICK-001',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/no_click.jpg',
            'delivery_status' => 'pending',
        ]);
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.template_resend']],
            ], 200),
        ]);

        $this->actingAs($this->admin)->post(route('admin.resend.ticket', $ticket->id))->assertRedirect();

        Http::assertSent(fn ($request) => ($request['type'] ?? null) === 'template');
        Http::assertNotSent(fn ($request) => ($request['type'] ?? null) === 'image');
        $ticket->refresh();
        $booking->refresh();
        $this->assertSame('pending', $ticket->delivery_status);
        $this->assertNull($ticket->claimed_at);
        $this->assertFalse($ticket->isTicketDeliveredOrReceived());
        $this->assertSame('delivered', $booking->template_status, 'A resent template must not downgrade a confirmed delivery webhook.');
        $this->assertSame('wamid.template_resend', $booking->template_message_id);
        $this->assertNull($booking->template_error);
    }


    public function test_bulk_resend_before_customer_click_sends_one_template_for_booking_phone()
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'حجز متعدد بلا نقر',
            'phone' => '01012345678',
            'tickets_count' => 2,
            'total_price' => 300,
            'status' => 'approved',
            'template_status' => 'delivered',
            'template_message_id' => 'wamid.template_existing_bulk',
            'reference_code' => 'SRX-NO-CLICK-BULK',
        ]);
        $tickets = collect(range(1, 2))->map(fn ($n) => Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'حجز متعدد بلا نقر '.$n,
            'phone' => '01012345678',
            'ticket_code' => 'TICK-NO-CLICK-BULK-'.$n,
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/no_click_bulk_'.$n.'.jpg',
            'delivery_status' => 'pending',
        ]));
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.template_bulk_resend']],
            ], 200),
        ]);

        $this->actingAs($this->admin)->post(route('admin.bookings.resend-all', $booking))->assertRedirect();

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => ($request['type'] ?? null) === 'template');
        foreach ($tickets as $ticket) {
            $ticket->refresh();
            $this->assertSame('pending', $ticket->delivery_status);
            $this->assertNull($ticket->claimed_at);
            $this->assertNull($ticket->delivery_error);
        }
        $booking->refresh();
        $this->assertSame('wamid.template_bulk_resend', $booking->template_message_id);
        $this->assertSame('بانتظار نقر العميل', $booking->delivery_badge['text']);
    }

    /**
     * Requirement 5 — Scenario B: 24h Meta conversation window is closed (Error 131047).
     * System automatically catches error, falls back to approved template,
     * informs admin with clear warning alert, and flags ticket pending customer tap.
     */
    public function test_scenario_b_fallback_to_template_when_24h_window_closed_error_131047()
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'كريم فهمي',
            'phone' => '01511223344',
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'sent',
            'template_sent_at' => now()->subDays(3),
            'reference_code' => 'SRX-KAREEM-001',
        ]);

        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'كريم فهمي',
            'phone' => '01511223344',
            'ticket_code' => 'TICK-KAREEM-001',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/ticket_kareem.jpg',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
            'claimed_at' => now()->subDays(3),
        ]);

        // Mock Meta WhatsApp Graph API:
        // 1st call (image send): returns 400 error 131047 (window closed)
        // 2nd call (template fallback): returns 200 success
        Http::fake([
            'https://graph.facebook.com/*' => Http::sequence()
                ->push([
                    'error' => [
                        'message' => 'Message failed to send because more than 24 hours have passed since the customer last replied to this number.',
                        'type' => 'OAuthException',
                        'code' => 131047,
                        'fbtrace_id' => 'fake_trace_123',
                    ],
                ], 400)
                ->push([
                    'messaging_product' => 'whatsapp',
                    'contacts' => [['input' => '01511223344', 'wa_id' => '201511223344']],
                    'messages' => [['id' => 'wamid.HBgLMjAx...']],
                ], 200),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.resend.ticket', $ticket->id));

        $response->assertRedirect();
        $response->assertSessionHas('status');
        $statusMsg = session('status');
        $this->assertStringContainsString('نافذة الـ 24 ساعة مغلقة مع العميل', $statusMsg);
        $this->assertStringContainsString('تم إرسال قالب استلام التذكرة', $statusMsg);

        $ticket->refresh();
        $booking->refresh();

        $this->assertEquals('pending', $ticket->delivery_status);
        $this->assertNull($ticket->claimed_at, 'Recovery template starts a new customer-click cycle.');
        $this->assertNull($ticket->delivery_error, 'A successful template recovery is not left as a ticket failure.');
        $this->assertEquals('sent', $booking->template_status);
        $this->assertNotNull($booking->template_sent_at);
        $this->assertTrue($booking->isAwaitingCustomerClick());
        $this->assertSame('بانتظار نقر العميل', $booking->delivery_badge['text']);
    }

    /**
     * Requirement 5 — Scenario C: Customer already received ticket or ticket was previously sent.
     * Admin explicitly re-sends. System records resend_requested_at and delivers without corrupting state.
     */
    public function test_scenario_c_authorized_resend_updates_resend_requested_at_and_delivers()
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'نور الهدى',
            'phone' => '01099887766',
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'sent',
            'reference_code' => 'SRX-NOOR-001',
        ]);

        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'نور الهدى',
            'phone' => '01099887766',
            'ticket_code' => 'TICK-NOOR-001',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/ticket_noor.jpg',
            'whatsapp_sent' => true,
            'delivery_status' => 'delivered',
            'last_delivered_at' => now()->subDay(),
        ]);

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.HBgLMjAx_resend...']],
            ], 200),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.resend.ticket', $ticket->id));

        $response->assertRedirect();
        $response->assertSessionHas('status', '✅ تم إرسال صورة التذكرة بنجاح عبر واتساب مباشرة.');

        $ticket->refresh();
        $this->assertTrue((bool) $ticket->whatsapp_sent);
        $this->assertEquals('delivered', $ticket->delivery_status, 'Resend acceptance must preserve confirmed delivery.');
        $this->assertNotNull($ticket->resend_requested_at);
        $this->assertNull($ticket->delivery_error);
    }

    /**
     * Requirement 6: Bulk resend action sends all tickets for a booking.
     */
    public function test_bulk_resend_all_tickets_in_booking_success()
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'عائلة حسن',
            'phone' => '01122334455',
            'tickets_count' => 2,
            'total_price' => 300,
            'status' => 'approved',
            'reference_code' => 'SRX-HASSAN-001',
        ]);

        $t1 = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'حسن أحمد',
            'phone' => '01122334455',
            'ticket_code' => 'TICK-H-001',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/th1.jpg',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        $t2 = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'منى حسن',
            'phone' => '01122334455',
            'ticket_code' => 'TICK-H-002',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/th2.jpg',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.HBgLMjAx_bulk...']],
            ], 200),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.bookings.resend-all', $booking));

        $response->assertRedirect();
        $response->assertSessionHas('status', '✅ أُرسل 2 تذكرة مباشرة، وأُعيد إرسال 0 قالب استلام.');

        $t1->refresh();
        $t2->refresh();

        $this->assertTrue((bool) $t1->whatsapp_sent);
        $this->assertEquals('sent', $t1->delivery_status);
        $this->assertTrue((bool) $t2->whatsapp_sent);
        $this->assertEquals('sent', $t2->delivery_status);
    }

    /**
     * Requirement 6: Bulk resend falls back to template if window is closed.
     */
    public function test_bulk_resend_all_tickets_triggers_template_if_window_closed()
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'زياد طارق',
            'phone' => '01009988776',
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'reference_code' => 'SRX-ZIAD-001',
        ]);

        Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'زياد طارق',
            'phone' => '01009988776',
            'ticket_code' => 'TICK-Z-001',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/tz1.jpg',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        // Sequence: 1st image send returns 131047, then template send succeeds
        Http::fake([
            'https://graph.facebook.com/*' => Http::sequence()
                ->push(['error' => ['code' => 131047, 'message' => 'window closed']], 400)
                ->push(['messaging_product' => 'whatsapp'], 200),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.bookings.resend-all', $booking));

        $response->assertRedirect();
        $response->assertSessionHas('status');
        $this->assertStringContainsString('نافذة الـ 24 ساعة مغلقة مع العميل', session('status'));

        $booking->refresh();
        $this->assertEquals('sent', $booking->template_status);
    }

    /**
     * Requirement 10: Resend all unreceived tickets targets ONLY unreceived tickets,
     * skips received tickets completely, and provides summary feedback.
     */
    public function test_resend_unreceived_tickets_targets_only_unreceived_and_skips_received()
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'ياسر جلال',
            'phone' => '01223344556',
            'tickets_count' => 2,
            'total_price' => 300,
            'status' => 'approved',
            'reference_code' => 'SRX-YASSER-001',
        ]);

        $tDelivered = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'ياسر جلال (1)',
            'phone' => '01223344556',
            'ticket_code' => 'TICK-YAS-1',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/ty1.jpg',
            'whatsapp_sent' => true,
            'delivery_status' => 'delivered',
            'last_delivered_at' => now()->subDay(),
        ]);

        $tPending = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'ياسر جلال (2)',
            'phone' => '01223344556',
            'ticket_code' => 'TICK-YAS-2',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/ty2.jpg',
            'whatsapp_sent' => false,
            'delivery_status' => 'failed',
            'delivery_error' => 'تعذر الاتصال بخادم واتساب',
        ]);

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'contacts' => [['input' => '01223344556', 'wa_id' => '201223344556']],
                'messages' => [['id' => 'wamid.HBgLMjAx...']],
            ], 200),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.bookings.resend-unreceived', $booking));

        $response->assertRedirect();
        $response->assertSessionHas('status');

        // Verify summary feedback format (✓ X تم تسليمها مباشرة · ↻ Y قوالب استلام أُرسلت · ⚠ Z تعذر إرسالها)
        $this->assertStringContainsString('1 تم تسليمها مباشرة', session('status'));

        // Delivered ticket was NOT resent or modified
        $tDelivered->refresh();
        $this->assertTrue((bool) $tDelivered->whatsapp_sent);
        $this->assertEquals('delivered', $tDelivered->delivery_status);

        // Pending ticket was successfully delivered
        $tPending->refresh();
        $this->assertTrue((bool) $tPending->whatsapp_sent);
        $this->assertEquals('sent', $tPending->delivery_status);

        // Exactly one HTTP request was made (for the pending ticket only)
        Http::assertSentCount(1);
    }

    /**
     * Requirement: Global Send Unreceived Tickets Action on Admin Bookings page.
     * The index page must render ONE global action button beside Back to Dashboard
     * with live count N, and MUST NOT render per-booking resend buttons on cards/rows.
     */
    public function test_admin_bookings_index_renders_global_send_unreceived_action_and_no_per_booking_resend_buttons()
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'طارق شوقي',
            'phone' => '01009988776',
            'tickets_count' => 2,
            'total_price' => 300,
            'status' => 'approved',
            'reference_code' => 'SRX-TAREQ-001',
        ]);

        Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'طارق شوقي (1)',
            'phone' => '01009988776',
            'ticket_code' => 'TICK-TRQ-1',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/trq1.jpg',
            'whatsapp_sent' => true,
            'delivery_status' => 'sent',
        ]);

        Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'طارق شوقي (2)',
            'phone' => '01009988776',
            'ticket_code' => 'TICK-TRQ-2',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/trq2.jpg',
            'whatsapp_sent' => false,
            'delivery_status' => 'failed',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.bookings.index'));

        $response->assertStatus(200);

        // Global action button with live count N (1 unreceived ticket)
        $response->assertSee('إرسال التذاكر غير المستلمة (');
        $response->assertSee('data-live-count="unreceived">1', false);
        $response->assertSee('adm-global-resend-modal');
        $response->assertSee('سيتم إرسال التذاكر غير المستلمة إلى أصحابها — العدد:');

        // MUST NOT render per-booking "إعادة المعلق" buttons anywhere on the index page
        $response->assertDontSee('إعادة المعلق');
    }

    /**
     * Requirement: Global Send Unreceived Tickets Action Execution.
     * Processes all eligible unreceived tickets across the entire bookings dataset,
     * skips already received/sent tickets, skips unapproved bookings, falls back to
     * templates on Meta error 131047, and returns an accurate result summary.
     */
    public function test_global_send_all_unreceived_tickets_processes_all_eligible_bookings_and_skips_received_and_unapproved()
    {
        // Booking 1: Approved - 1 received ticket, 1 unreceived ticket (24h open)
        $booking1 = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'حازم إمام',
            'phone' => '01111111111',
            'tickets_count' => 2,
            'total_price' => 300,
            'status' => 'approved',
            'reference_code' => 'SRX-HAZ-001',
        ]);

        $b1Delivered = Ticket::create([
            'booking_id' => $booking1->id,
            'name' => 'حازم (1)',
            'phone' => '01111111111',
            'ticket_code' => 'TICK-HAZ-1',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/h1.jpg',
            'whatsapp_sent' => true,
            'delivery_status' => 'sent',
        ]);

        $b1Unreceived = Ticket::create([
            'booking_id' => $booking1->id,
            'name' => 'حازم (2)',
            'phone' => '01111111111',
            'ticket_code' => 'TICK-HAZ-2',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/h2.jpg',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        // Booking 2: Approved - 1 unreceived ticket (24h window closed: Meta 131047)
        $booking2 = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'محمد بركات',
            'phone' => '01222222222',
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'reference_code' => 'SRX-BRK-002',
        ]);

        $b2Unreceived = Ticket::create([
            'booking_id' => $booking2->id,
            'name' => 'بركات',
            'phone' => '01222222222',
            'ticket_code' => 'TICK-BRK-1',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/b1.jpg',
            'whatsapp_sent' => false,
            'delivery_status' => 'failed',
        ]);

        // Booking 3: PENDING - Should NOT be processed by global resend!
        $booking3 = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'عماد متعب',
            'phone' => '01333333333',
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'pending',
            'reference_code' => 'SRX-MTB-003',
        ]);

        $b3Unapproved = Ticket::create([
            'booking_id' => $booking3->id,
            'name' => 'متعب',
            'phone' => '01333333333',
            'ticket_code' => 'TICK-MTB-1',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/m1.jpg',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $data = $request->data();

            // Direct image to 01111111111 succeeds
            if (isset($data['type']) && $data['type'] === 'image' && ($data['to'] ?? '') === '01111111111') {
                return Http::response([
                    'messaging_product' => 'whatsapp',
                    'messages' => [['id' => 'wamid.HBgLMjAx_hazem...']],
                ], 200);
            }

            // Direct image to 01222222222 fails with 131047
            if (isset($data['type']) && $data['type'] === 'image' && ($data['to'] ?? '') === '01222222222') {
                return Http::response([
                    'error' => [
                        'message' => 'Message failed to send because more than 24 hours have passed.',
                        'type' => 'OAuthException',
                        'code' => 131047,
                    ],
                ], 400);
            }

            // Fallback template to 01222222222 succeeds
            if (isset($data['type']) && $data['type'] === 'template' && ($data['to'] ?? '') === '01222222222') {
                return Http::response([
                    'messaging_product' => 'whatsapp',
                    'messages' => [['id' => 'wamid.HBgLMjAx_template_barakat...']],
                ], 200);
            }

            return Http::response(['error' => 'unexpected destination'], 500);
        });

        $response = $this->actingAs($this->admin)->post(route('admin.bookings.send-all-unreceived'));

        $response->assertRedirect(route('admin.bookings.index'));
        $response->assertSessionHas('status');

        $statusMsg = session('status');

        // Check the 4 required metrics in summary:
        // - Direct tickets sent: 1
        // - Templates sent: 1
        // - Failed: 0
        // - Skipped/already received: 1
        $this->assertStringContainsString('تم إرسال 1 تذكرة مباشرة', $statusMsg);
        $this->assertStringContainsString('أُرسل 1 قالب استلام', $statusMsg);
        $this->assertStringContainsString('تعذر إرسال 0', $statusMsg);
        $this->assertStringContainsString('تم تخطي 1 تذكرة مستلمة مسبقاً', $statusMsg);

        // Booking 1 unreceived is now sent
        $b1Unreceived->refresh();
        $this->assertTrue((bool) $b1Unreceived->whatsapp_sent);
        $this->assertEquals('sent', $b1Unreceived->delivery_status);

        // Booking 1 already delivered was skipped (not touched)
        $b1Delivered->refresh();
        $this->assertTrue((bool) $b1Delivered->whatsapp_sent);
        $this->assertEquals('sent', $b1Delivered->delivery_status);

        // Booking 2 fell back to template and updated template status
        $b2Unreceived->refresh();
        $this->assertFalse((bool) $b2Unreceived->whatsapp_sent);
        $this->assertEquals('pending', $b2Unreceived->delivery_status);
        $booking2->refresh();
        $this->assertEquals('sent', $booking2->template_status);

        // Booking 3 (pending/unapproved) was completely skipped
        $b3Unapproved->refresh();
        $this->assertFalse((bool) $b3Unapproved->whatsapp_sent);
        $this->assertEquals('pending', $b3Unapproved->delivery_status);

        // Verify that visiting the index now shows N = 0
        // (since b1 is sent and b2 has template sent and b3 is pending)
        // Wait, b2 still has whatsapp_sent = false and delivery_status = 'pending'!
        // So b2 is still 1 unreceived until customer taps template.
        $indexResponse = $this->actingAs($this->admin)->get(route('admin.bookings.index'));
        $indexResponse->assertSee('إرسال التذاكر غير المستلمة (');
        $indexResponse->assertSee('data-live-count="unreceived">1', false);
    }

    /**
     * Requirement 19: Phone not on WhatsApp (Meta error 131026).
     * Template fails -> UI must show Template FAILED, Customer NOT RECEIVED, Ticket NOT SENT.
     * MUST NOT show "تم استلام التذكرة".
     */
    public function test_scenario_19_phone_not_registered_on_whatsapp_meta_fails()
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'طارق غير مسجل',
            'phone' => '01099999999',
            'reference_code' => 'REF-TEST-19',
            'public_number' => 119,
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'not_sent',
            'template_sent_at' => null,
        ]);

        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'طارق غير مسجل',
            'phone' => '01099999999',
            'ticket_code' => 'TICK-UNREG-1',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/unreg.jpg',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        // Mock Meta API: direct send fails with window closed, template fails with 131026 (not on WhatsApp)
        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $data = $request->data();

            if (($data['type'] ?? '') === 'image') {
                return Http::response([
                    'error' => [
                        'message' => 'Message failed to send because more than 24 hours have passed.',
                        'type' => 'OAuthException',
                        'code' => 131047,
                    ],
                ], 400);
            }

            if (($data['type'] ?? '') === 'template') {
                return Http::response([
                    'error' => [
                        'message' => 'Message undeliverable. The phone number is not registered on WhatsApp.',
                        'type' => 'OAuthException',
                        'code' => 131026,
                    ],
                ], 400);
            }

            return Http::response(['error' => 'unknown'], 500);
        });

        // Admin clicks Resend for the ticket
        $resendResponse = $this->actingAs($this->admin)->post(route('admin.resend.ticket', $ticket->id));
        $resendResponse->assertRedirect();

        // Refresh booking & ticket from DB
        $booking->refresh();
        $ticket->refresh();

        $this->assertEquals('failed', $booking->template_status);
        $this->assertStringContainsString('رقم الهاتف غير مسجل على WhatsApp', (string) $booking->template_error);
        $this->assertFalse((bool) $ticket->whatsapp_sent);
        $this->assertNull($ticket->claimed_at);

        // View booking details in UI
        $uiResponse = $this->actingAs($this->admin)->get(route('admin.bookings.show', $booking));
        $uiResponse->assertStatus(200);

        // Stage 1: Template shows failure and exact error hint
        $uiResponse->assertSee('فشل إرسال قالب استلام التذاكر');
        $uiResponse->assertSee('لم يصل قالب الاستلام');
        $uiResponse->assertSee('رقم الهاتف غير مسجل على WhatsApp');

        // Stage 2: Customer action shows not received / failed
        $uiResponse->assertSee('تعذر (فشل القالب)');

        // Stage 3: Ticket shows failed to send / not delivered
        $uiResponse->assertSee('فشل الإرسال');
        $uiResponse->assertSee('التذكرة لم تصل للعميل');

        // MUST NOT show false success
        $uiResponse->assertDontSee('تم تسليم جميع التذاكر بالكامل');
    }

    /**
     * Requirement 20: Template delivered -> Customer clicks Receive -> Ticket send fails.
     * UI must clearly distinguish: Template delivered, Customer clicked, Ticket delivery failed.
     * MUST NOT show "تم استلام التذكرة".
     */
    public function test_scenario_20_customer_clicks_receive_but_ticket_send_fails()
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'ياسمين فشل التذكرة',
            'phone' => '01055555555',
            'reference_code' => 'REF-TEST-20',
            'public_number' => 120,
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'delivered',
            'template_sent_at' => now()->subMinutes(10),
            'template_message_id' => 'wamid.HBgLMjAx_tpl_yasmin',
        ]);

        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'ياسمين فشل التذكرة',
            'phone' => '01055555555',
            'ticket_code' => 'TICK-YAS-001',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/yas.jpg',
            'whatsapp_sent' => false,
            'claimed_at' => now()->subMinutes(5),
            'delivery_status' => 'failed',
            'delivery_error' => 'تعذر إرسال صورة التذكرة: Meta error 131053',
        ]);

        $uiResponse = $this->actingAs($this->admin)->get(route('admin.bookings.show', $booking));
        $uiResponse->assertStatus(200);

        // Stage 1: Template delivered
        $uiResponse->assertSee('تم التسليم');

        // Stage 2: Customer clicked receive
        $uiResponse->assertSee('نقر العميل');

        // Stage 3: Ticket delivery failed with specific reason
        $uiResponse->assertSee('فشل الإرسال');
        $uiResponse->assertSee('تعذر تسليم 1 تذكرة');
        $uiResponse->assertSee('التذكرة لم تصل للعميل');
        $uiResponse->assertSee('تعذر إرسال صورة التذكرة: Meta error 131053');

        // MUST NOT show full success
        $uiResponse->assertDontSee('تم تسليم جميع التذاكر بالكامل');
    }

    /**
     * Requirement 21: Full success scenario:
     * Template delivered -> Customer clicks Receive -> Ticket sent -> Webhook confirms delivered.
     * Only then does the UI show the final successful state.
     */
    public function test_scenario_21_end_to_end_delivery_success()
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'محمود نجاح كامل',
            'phone' => '01066666666',
            'reference_code' => 'REF-TEST-21',
            'public_number' => 121,
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'delivered',
            'template_sent_at' => now()->subMinutes(15),
            'template_message_id' => 'wamid.HBgLMjAx_tpl_success',
        ]);

        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'محمود نجاح كامل',
            'phone' => '01066666666',
            'ticket_code' => 'TICK-SUC-001',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/suc.jpg',
            'whatsapp_sent' => true,
            'whatsapp_message_id' => 'wamid.HBgLMjAx_ticket_success',
            'claimed_at' => now()->subMinutes(10),
            'last_delivered_at' => now()->subMinutes(9),
            'delivery_status' => 'delivered',
            'delivery_error' => null,
        ]);

        $uiResponse = $this->actingAs($this->admin)->get(route('admin.bookings.show', $booking));
        $uiResponse->assertStatus(200);

        // Final successful state
        $uiResponse->assertSee('تم تسليم جميع التذاكر بالكامل (1/1)');
        $uiResponse->assertSee('وصلت جميع التذاكر على هاتف العميل بالكامل');
        $uiResponse->assertDontSee('تعذر تسليم');
        $uiResponse->assertDontSee('فشل الإرسال');
    }

    /**
     * Requirement 9: Webhook asynchronous status 'failed' (e.g. 131026)
     * must transition state to failed, set whatsapp_sent=false, record error hint,
     * and NOT log UNEXPECTED_WHATSAPP_STATUS or silently turn into success.
     */
    public function test_webhook_asynchronous_status_failed_updates_db_and_shows_error_hint()
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'نادر اختبار الويب هوك',
            'phone' => '01044444444',
            'reference_code' => 'REF-WH-01',
            'public_number' => 125,
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'sent',
            'template_sent_at' => now()->subMinutes(2),
            'template_message_id' => 'wamid.HBgLMjAx_tpl_webhook_fail',
        ]);

        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'نادر اختبار الويب هوك',
            'phone' => '01044444444',
            'ticket_code' => 'TICK-WH-001',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/wh.jpg',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        // Meta status webhook: failed with error 131026
        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'statuses' => [
                                    [
                                        'id' => 'wamid.HBgLMjAx_tpl_webhook_fail',
                                        'status' => 'failed',
                                        'timestamp' => (string) now()->timestamp,
                                        'recipient_id' => '201044444444',
                                        'errors' => [
                                            [
                                                'code' => 131026,
                                                'title' => 'Message undeliverable',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/webhook/whatsapp', $payload);
        $response->assertStatus(200);

        $booking->refresh();
        $ticket->refresh();

        $this->assertEquals('failed', $booking->template_status);
        $this->assertStringContainsString('رقم الهاتف غير مسجل على WhatsApp', (string) $booking->template_error);
        $this->assertEquals('failed', $ticket->delivery_status);
        $this->assertFalse((bool) $ticket->whatsapp_sent);

        // Verify in Admin UI
        $uiResponse = $this->actingAs($this->admin)->get(route('admin.bookings.show', $booking));
        $uiResponse->assertStatus(200);
        $uiResponse->assertSee('فشل إرسال قالب استلام التذاكر');
        $uiResponse->assertSee('رقم الهاتف غير مسجل على WhatsApp');
        $uiResponse->assertDontSee('تم تسليم جميع التذاكر بالكامل');
    }

    /**
     * Requirement 9: Webhook asynchronous status 'delivered'
     * marks ticket confirmed delivered.
     */
    public function test_webhook_asynchronous_status_delivered_marks_ticket_delivered()
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'سارة تسليم مؤكد',
            'phone' => '01077777777',
            'reference_code' => 'REF-WH-02',
            'public_number' => 126,
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'delivered',
            'template_sent_at' => now()->subMinutes(10),
            'template_message_id' => 'wamid.HBgLMjAx_tpl_ok',
        ]);

        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'سارة تسليم مؤكد',
            'phone' => '01077777777',
            'ticket_code' => 'TICK-WH-002',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/wh2.jpg',
            'whatsapp_sent' => true,
            'whatsapp_message_id' => 'wamid.HBgLMjAx_ticket_delivered_test',
            'claimed_at' => now()->subMinutes(5),
            'delivery_status' => 'sent',
        ]);

        // Meta status webhook: delivered
        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'statuses' => [
                                    [
                                        'id' => 'wamid.HBgLMjAx_ticket_delivered_test',
                                        'status' => 'delivered',
                                        'timestamp' => (string) now()->timestamp,
                                        'recipient_id' => '201077777777',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/webhook/whatsapp', $payload);
        $response->assertStatus(200);

        $ticket->refresh();
        $this->assertEquals('delivered', $ticket->delivery_status);
        $this->assertTrue((bool) $ticket->whatsapp_sent);
        $this->assertNotNull($ticket->last_delivered_at);

        // Verify in Admin UI
        $uiResponse = $this->actingAs($this->admin)->get(route('admin.bookings.show', $booking));
        $uiResponse->assertStatus(200);
        $uiResponse->assertSee('تم تسليم جميع التذاكر بالكامل (1/1)');
    }

    /**
     * Requirement 10: Delivered tickets remain delivered across reloads
     * and are never overwritten or re-sent.
     */
    public function test_delivered_ticket_remains_delivered_after_page_reload()
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'ماجد تسليم دائم',
            'phone' => '01099998888',
            'reference_code' => 'REF-DELIV-01',
            'public_number' => 199,
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'delivered',
            'template_sent_at' => now()->subHours(2),
        ]);

        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'ماجد تسليم دائم',
            'phone' => '01099998888',
            'ticket_code' => 'TICK-DELIV-99',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/deliv.jpg',
            'whatsapp_sent' => true,
            'whatsapp_message_id' => 'wamid.HBgLMjAx_delivered_perm',
            'claimed_at' => now()->subHour(),
            'delivery_status' => 'delivered',
            'last_delivered_at' => now()->subMinutes(50),
        ]);

        // First page load
        $res1 = $this->actingAs($this->admin)->get(route('admin.bookings.show', $booking));
        $res1->assertStatus(200);
        $res1->assertSee('تم تسليم جميع التذاكر بالكامل (1/1)');
        $res1->assertSee('تم التسليم');

        // Reload page
        $res2 = $this->actingAs($this->admin)->get(route('admin.bookings.show', $booking));
        $res2->assertStatus(200);
        $res2->assertSee('تم تسليم جميع التذاكر بالكامل (1/1)');
        $res2->assertSee('تم التسليم');

        // Verify ticket state remains untouched
        $ticket->refresh();
        $this->assertEquals('delivered', $ticket->delivery_status);
        $this->assertTrue((bool) $ticket->whatsapp_sent);
        $this->assertNotNull($ticket->last_delivered_at);
    }

    /**
     * Requirement 11: Admin poll status endpoint returns live operational metrics,
     * unreceived counts, and per-booking delivery states for live dashboard updates.
     */
    public function test_admin_poll_status_endpoint_returns_live_counts_and_booking_metrics()
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'كريم إحصاء مباشر',
            'phone' => '01155554444',
            'reference_code' => 'REF-POLL-01',
            'public_number' => 201,
            'tickets_count' => 2,
            'total_price' => 300,
            'status' => 'approved',
            'template_status' => 'delivered',
        ]);

        $ticket1 = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'كريم تذكرة 1',
            'phone' => '01155554444',
            'ticket_code' => 'TICK-P1',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/p1.jpg',
            'whatsapp_sent' => true,
            'delivery_status' => 'delivered',
        ]);

        $ticket2 = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'كريم تذكرة 2',
            'phone' => '01155554444',
            'ticket_code' => 'TICK-P2',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/p2.jpg',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        // Global poll without booking_id
        $resGlobal = $this->actingAs($this->admin)->getJson(route('admin.bookings.poll-status'));
        $resGlobal->assertStatus(200);
        $this->assertStringContainsString('no-store', (string) $resGlobal->headers->get('Cache-Control'));
        $resGlobal->assertJsonStructure([
            'ok',
            'summary' => [
                'total_bookings',
                'pending_bookings',
                'approved_bookings',
                'rejected_bookings',
                'unreceived_tickets_count',
                'latest_booking_id',
                'issue_bookings',
            ],
            'booking',
        ]);
        $this->assertGreaterThanOrEqual(1, $resGlobal->json('summary.unreceived_tickets_count'));

        // Scoped poll with booking_id
        $resBooking = $this->actingAs($this->admin)->getJson(route('admin.bookings.poll-status', ['booking_id' => $booking->id]));
        $resBooking->assertStatus(200);
        $resBooking->assertJson([
            'ok' => true,
            'booking' => [
                'id' => $booking->id,
                'metrics' => [
                    'total_count' => 2,
                    'delivered_count' => 1,
                    'sent_count' => 0,
                    'is_all_delivered' => false,
                ],
            ],
        ]);
    }

    public function test_admin_index_poll_returns_incremental_desktop_and_mobile_booking_markup(): void
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'حجز مباشر للاختبار',
            'phone' => '01012345678',
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'reference_code' => 'LIVE-ROW-01',
            'template_status' => 'sent',
        ]);
        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'حجز مباشر للاختبار',
            'phone' => '01012345678',
            'ticket_code' => 'LIVE-ROW-T1',
            'delivery_status' => 'pending',
        ]);

        $full = $this->actingAs($this->admin)->getJson(route('admin.bookings.poll-status', ['index' => 1]));
        $full->assertOk()->assertJsonPath('index_rows.complete', true);
        $this->assertStringContainsString('data-booking-id="'.$booking->id.'"', $full->json('index_rows.table'));
        $this->assertStringContainsString('data-booking-id="'.$booking->id.'"', $full->json('index_rows.mobile'));

        $ticket->update(['delivery_status' => 'failed', 'delivery_error' => '131026']);
        $incremental = $this->actingAs($this->admin)->getJson(route('admin.bookings.poll-status', [
            'index' => 1,
            'since' => now()->subMinute()->toISOString(),
        ]));
        $incremental->assertOk()->assertJsonPath('index_rows.complete', false);
        $this->assertStringContainsString('data-booking-id="'.$booking->id.'"', $incremental->json('index_rows.table'));
        $this->assertStringContainsString('فشل الإرسال', $incremental->json('index_rows.table'));
    }


    public function test_poll_includes_new_booking_even_when_its_commit_timestamp_precedes_poll_cursor()
    {
        $afterId = (int) (Booking::max('id') ?? 0);
        $oldTimestamp = now()->subMinute();
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'حجز متأخر الالتزام',
            'phone' => '01012345678',
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'pending',
            'reference_code' => 'POLL-OLD-TIMESTAMP',
            'created_at' => $oldTimestamp,
            'updated_at' => $oldTimestamp,
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('admin.bookings.poll-status', [
            'index' => 1,
            'since' => now()->toISOString(),
            'after_id' => $afterId,
        ]));

        $response->assertOk()->assertJsonPath('index_rows.complete', false);
        $this->assertStringContainsString('data-booking-id="'.$booking->id.'"', $response->json('index_rows.table'));
    }

    public function test_cta_with_multiple_eligible_bookings_for_same_phone_drains_all_approved_bookings(): void
    {
        Queue::fake();
        Http::fake();
        $ticketIds = [];
        foreach (['AMBIGUOUS-1', 'AMBIGUOUS-2'] as $reference) {
            $booking = Booking::create([
                'show_time_id' => $this->showTime->id,
                'full_name' => $reference,
                'phone' => '01012345678',
                'tickets_count' => 1,
                'total_price' => 150,
                'status' => 'approved',
                'reference_code' => $reference,
                'template_status' => 'delivered',
            ]);
            $ticketIds[] = Ticket::create([
                'booking_id' => $booking->id,
                'name' => $reference,
                'phone' => '01012345678',
                'ticket_code' => $reference.'-T1',
                'delivery_status' => 'pending',
            ])->id;
        }

        $this->postJson('/webhook/whatsapp', [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messages' => [[
                            'id' => 'wamid.inbound_ambiguous_cta',
                            'from' => '201012345678',
                            'type' => 'button',
                            'button' => ['payload' => 'receive_ticket'],
                        ]],
                    ],
                ]],
            ]],
        ])->assertOk()->assertJson(['ok' => true]);

        foreach ($ticketIds as $id) {
            $this->assertNotNull(Ticket::findOrFail($id)->claimed_at);
            $this->assertEquals('pending', Ticket::findOrFail($id)->delivery_status);
        }
        Queue::assertPushed(\App\Jobs\SendWhatsAppTicketImageJob::class, 2);
    }

    /**
     * Regression Test 1: Initial template send results in accurate awaiting customer click state.
     */

    public function test_cta_context_message_id_validates_and_dispatches_all_undelivered_tickets_for_customer()
    {
        Queue::fake();
        Http::fake();
        $tickets = [];
        foreach (['CTX-BOOKING-A', 'CTX-BOOKING-B'] as $index => $reference) {
            $booking = Booking::create([
                'show_time_id' => $this->showTime->id,
                'full_name' => $reference,
                'phone' => '01012345678',
                'tickets_count' => 1,
                'total_price' => 150,
                'status' => 'approved',
                'reference_code' => $reference,
                'template_status' => 'delivered',
                'template_message_id' => $index === 0 ? 'wamid.template_ctx_a' : 'wamid.template_ctx_b',
            ]);
            $tickets[$reference] = Ticket::create([
                'booking_id' => $booking->id,
                'name' => $reference,
                'phone' => '01012345678',
                'ticket_code' => $reference.'-T1',
                'delivery_status' => 'pending',
            ]);
        }

        $this->postJson('/webhook/whatsapp', [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messages' => [[
                            'id' => 'wamid.inbound_ctx_b',
                            'from' => '201012345678',
                            'context' => ['id' => 'wamid.template_ctx_b'],
                            'type' => 'button',
                            'button' => ['payload' => 'receive_ticket'],
                        ]],
                    ],
                ]],
            ]],
        ])->assertOk();

        // Both approved bookings belonging to this customer are dispatched
        $this->assertNotNull($tickets['CTX-BOOKING-A']->fresh()->claimed_at);
        $this->assertNotNull($tickets['CTX-BOOKING-B']->fresh()->claimed_at);
        Queue::assertPushed(\App\Jobs\SendWhatsAppTicketImageJob::class, 2);
    }

    public function test_initial_template_send_results_in_awaiting_customer_click_state()
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'سارة علي',
            'phone' => '01011112222',
            'tickets_count' => 2,
            'total_price' => 300,
            'status' => 'approved',
            'template_status' => 'sent',
            'template_sent_at' => now(),
            'reference_code' => 'SRX-AWAIT-01',
        ]);

        Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'سارة علي 1',
            'phone' => '01011112222',
            'ticket_code' => 'TICK-AWAIT-1',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/s1.jpg',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'سارة علي 2',
            'phone' => '01011112222',
            'ticket_code' => 'TICK-AWAIT-2',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/s2.jpg',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        $booking->refresh();
        $this->assertTrue($booking->isAwaitingCustomerClick());
        $this->assertEquals('بانتظار نقر العميل', $booking->delivery_badge['text']);

        $resIndex = $this->actingAs($this->admin)->get(route('admin.bookings.index'));
        $resIndex->assertStatus(200);
        $resIndex->assertSee('بانتظار نقر العميل');

        $resShow = $this->actingAs($this->admin)->get(route('admin.bookings.show', $booking->id));
        $resShow->assertStatus(200);
        $resShow->assertSee('أُرسل القالب · بانتظار نقر العميل على واتساب');
    }

    /**
     * Regression Test 2: Inbound receive-ticket CTA supports both local (01XXXXXXXXX) and international (201XXXXXXXXX) Egyptian phone formats.
     */
    public function test_inbound_receive_ticket_supports_both_local_and_international_egyptian_phone_formats()
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'محمود فوزي',
            'phone' => '01222353657', // Local 11-digit Egyptian format
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'sent',
            'reference_code' => 'SRX-PHONE-01',
        ]);

        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'محمود فوزي',
            'phone' => '01222353657',
            'ticket_code' => 'TICK-PHONE-1',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/mf1.jpg',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'contacts' => [['input' => '201222353657', 'wa_id' => '201222353657']],
                'messages' => [['id' => 'wamid.HBgLMjAx_phone_norm_test']],
            ], 200),
        ]);

        // Meta delivers inbound message with international 12-digit format '201222353657'
        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'id' => 'wamid.inbound_cta_norm_01',
                                        'from' => '201222353657',
                                        'type' => 'button',
                                        'button' => [
                                            'text' => 'استلام التذكرة',
                                            'payload' => 'receive_ticket',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $res = $this->postJson('/webhook/whatsapp', $payload);
        $res->assertStatus(200);
        $res->assertJson(['ok' => true]);

        $ticket->refresh();
        $this->assertNotNull($ticket->claimed_at, 'Ticket claimed_at must be populated after matching normalized phone.');
        $this->assertTrue((bool) $ticket->whatsapp_sent);
    }

    /**
     * Regression Test 3: Single-ticket end-to-end pipeline execution from CTA to image send.
     */
    public function test_single_ticket_end_to_end_flow_claims_and_delivers_correctly()
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'ياسمين حسام',
            'phone' => '01099887766',
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'sent',
            'reference_code' => 'SRX-SINGLE-01',
        ]);

        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'ياسمين حسام',
            'phone' => '01099887766',
            'ticket_code' => 'TICK-SINGLE-1',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/single_qr.jpg',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'contacts' => [['input' => '01099887766', 'wa_id' => '201099887766']],
                'messages' => [['id' => 'wamid.HBgLMjAx_single_end2end']],
            ], 200),
        ]);

        // Customer clicks CTA
        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'id' => 'wamid.inbound_single_tap',
                                        'from' => '201099887766',
                                        'type' => 'text',
                                        'text' => ['body' => 'أستلام التذكرة'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->postJson('/webhook/whatsapp', $payload)->assertStatus(200);

        // Run the dispatched image job directly
        $job = new \App\Jobs\SendWhatsAppTicketImageJob($ticket->id);
        app()->call([$job, 'handle']);

        $this->confirmTicketDelivery($ticket);

        $ticket->refresh();
        $this->assertTrue((bool) $ticket->whatsapp_sent);
        $this->assertNotNull($ticket->last_delivered_at);
        $this->assertNotNull($ticket->claimed_at);
        $this->assertEquals('wamid.HBgLMjAx_single_end2end', $ticket->whatsapp_message_id);
        $this->assertTrue($ticket->isTicketDeliveredOrReceived());

        $booking->refresh();
        $this->assertEquals(1, $booking->ticketsDeliveredCount());
        $this->assertEquals(0, $booking->ticketsSentCount());
        $this->assertFalse($booking->isAwaitingCustomerClick());
        $this->assertEquals('تم الاستلام (1/1)', $booking->delivery_badge['text']);

        // Check Admin Index
        $resIndex = $this->actingAs($this->admin)->get(route('admin.bookings.index'));
        $resIndex->assertSee('تم الاستلام (1/1)');

        // Check Admin Show
        $resShow = $this->actingAs($this->admin)->get(route('admin.bookings.show', $booking->id));
        $resShow->assertSee('تم تسليم جميع التذاكر بالكامل (1/1)');
        $resShow->assertDontSee('بانتظار نقر العميل على واتساب');

        // Check Polling endpoint
        $resPoll = $this->actingAs($this->admin)->getJson(route('admin.bookings.poll-status', ['booking_id' => $booking->id]));
        $resPoll->assertJson([
            'ok' => true,
            'booking' => [
                'id' => $booking->id,
                'metrics' => [
                    'total_count' => 1,
                    'delivered_count' => 1,
                    'sent_count' => 0,
                    'is_all_delivered' => true,
                    'awaiting_click' => false,
                ],
            ],
        ]);
    }

    /**
     * Regression Test 4: 5-ticket multi-ticket booking aggregates cleanly.
     */
    public function test_multi_ticket_5_tickets_aggregates_properly_through_pipeline()
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'طارق كمال',
            'phone' => '01022334455',
            'tickets_count' => 5,
            'total_price' => 750,
            'status' => 'approved',
            'template_status' => 'sent',
            'reference_code' => 'SRX-FIVE-01',
        ]);

        $ticketIds = [];
        for ($i = 1; $i <= 5; $i++) {
            $t = Ticket::create([
                'booking_id' => $booking->id,
                'name' => "طارق تذكرة {$i}",
                'phone' => '01022334455',
                'ticket_code' => "TICK-FIVE-{$i}",
                'qr_image_path' => "https://res.cloudinary.com/demo/image/upload/v1234/qr_5_{$i}.jpg",
                'whatsapp_sent' => false,
                'delivery_status' => 'pending',
            ]);
            $ticketIds[] = $t->id;
        }

        $booking->refresh();
        $this->assertTrue($booking->isAwaitingCustomerClick());
        $this->assertEquals(0, $booking->ticketsDeliveredCount());

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            static $callCount = 0;
            $callCount++;
            return Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => "wamid.five_ticket_{$callCount}"]],
            ], 200);
        });

        // Customer taps receive ticket once
        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'id' => 'wamid.inbound_five_tap',
                                        'from' => '201022334455',
                                        'type' => 'button',
                                        'button' => ['text' => 'استلام التذكرة'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->postJson('/webhook/whatsapp', $payload)->assertStatus(200);

        // Execute all 5 image send jobs
        foreach ($ticketIds as $id) {
            $job = new \App\Jobs\SendWhatsAppTicketImageJob($id);
            app()->call([$job, 'handle']);
        }

        foreach ($ticketIds as $id) {
            $this->confirmTicketDelivery(Ticket::findOrFail($id));
        }

        $booking->refresh();
        $this->assertEquals(5, $booking->ticketsDeliveredCount());
        $this->assertEquals(0, $booking->ticketsSentCount());
        $this->assertFalse($booking->isAwaitingCustomerClick());
        $this->assertEquals('تم الاستلام (5/5)', $booking->delivery_badge['text']);

        $resPoll = $this->actingAs($this->admin)->getJson(route('admin.bookings.poll-status', ['booking_id' => $booking->id]));
        $resPoll->assertJson([
            'ok' => true,
            'booking' => [
                'id' => $booking->id,
                'metrics' => [
                    'total_count' => 5,
                    'delivered_count' => 5,
                    'sent_count' => 0,
                    'is_all_delivered' => true,
                    'awaiting_click' => false,
                ],
            ],
        ]);
    }

    /**
     * Regression Test 5: Exact Booking #20 reproduction with 13 tickets.
     * Customer j, Phone 201222353657, 13 tickets dispatched and received on WhatsApp.
     * Must display 13/13 delivered and NEVER show "بانتظار نقر العميل" or stuck spinners.
     */
    public function test_multi_ticket_13_tickets_booking_20_exact_regression_reproduction()
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'j',
            'phone' => '201222353657',
            'public_number' => 20,
            'tickets_count' => 13,
            'total_price' => 1950,
            'status' => 'approved',
            'template_status' => 'sent',
            'template_sent_at' => now()->subHours(2),
            'reference_code' => 'SRX-BOOKING-20',
        ]);

        for ($i = 1; $i <= 13; $i++) {
            Ticket::create([
                'booking_id' => $booking->id,
                'name' => "j - تذكرة {$i}",
                'phone' => '201222353657',
                'ticket_code' => "TICK-B20-{$i}",
                'qr_image_path' => "https://res.cloudinary.com/demo/image/upload/v1234/b20_{$i}.jpg",
                'whatsapp_sent' => true,
                'delivery_status' => 'delivered',
                'whatsapp_message_id' => "wamid.HBgMMjAxMjIyMzUzNjU3FQIAERgSRTIwX1RJQ0tFVF8{$i}",
                'claimed_at' => now()->subHour(),
                'last_delivered_at' => now()->subHour(),
            ]);
        }

        $booking->refresh();

        // 1. Model aggregations
        $this->assertEquals(13, $booking->ticketsTotalCount());
        $this->assertEquals(13, $booking->ticketsDeliveredCount(), 'All 13 tickets must be recognized as delivered/received.');
        $this->assertEquals(0, $booking->ticketsSentCount(), 'Sent-to-network count must be 0 once tickets are received.');
        $this->assertEquals(0, $booking->ticketsSendingCount());
        $this->assertFalse($booking->isAwaitingCustomerClick(), 'Must not be awaiting click after claim.');

        // 2. Delivery Badge
        $badge = $booking->delivery_badge;
        $this->assertEquals('تم الاستلام (13/13)', $badge['text']);
        $this->assertEquals('emerald', $badge['type']);
        $this->assertFalse($badge['has_spinner']);

        // 3. Admin Index Rendering
        $resIndex = $this->actingAs($this->admin)->get(route('admin.bookings.index'));
        $resIndex->assertStatus(200);
        $resIndex->assertSee('تم الاستلام (13/13)');
        $resIndex->assertDontSee('أُرسلت للشبكة (13/13)');

        // 4. Admin Booking #20 Details Rendering
        $resShow = $this->actingAs($this->admin)->get(route('admin.bookings.show', $booking->id));
        $resShow->assertStatus(200);
        $resShow->assertSee('تم تسليم جميع التذاكر بالكامل (13/13)');
        $resShow->assertDontSee('أُرسل القالب · بانتظار نقر العميل على واتساب');
        $resShow->assertDontSee('بانتظار نقر العميل لإرسال صور التذاكر');

        // 5. Polling endpoint verification
        $resPoll = $this->actingAs($this->admin)->getJson(route('admin.bookings.poll-status', ['booking_id' => $booking->id]));
        $resPoll->assertStatus(200);
        $resPoll->assertJson([
            'ok' => true,
            'booking' => [
                'id' => $booking->id,
                'metrics' => [
                    'total_count' => 13,
                    'delivered_count' => 13,
                    'sent_count' => 0,
                    'sending_count' => 0,
                    'is_all_delivered' => true,
                    'awaiting_click' => false,
                ],
            ],
        ]);
    }

    /**
     * Regression Test 6: In-flight race condition: fast delivered webhook is NOT overwritten by send job.
     */
    public function test_job_webhook_race_condition_fast_delivered_webhook_is_not_overwritten_by_job()
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'سباق الشبكة',
            'phone' => '01055556666',
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'reference_code' => 'SRX-RACE-01',
        ]);

        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'سباق الشبكة',
            'phone' => '01055556666',
            'ticket_code' => 'TICK-RACE-1',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/race.jpg',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        $raceWamid = 'wamid.HBgLMjAx_race_delivered_fast';

        Http::fake(function () use ($ticket, $raceWamid) {
            // Meta's callback can beat persistence of messages[0].id. It must be
            // retained by WAMID and reconciled when the send job stores that ID.
            $this->postJson('/webhook/whatsapp', [
                'entry' => [[
                    'changes' => [[
                        'value' => [
                            'statuses' => [[
                                'id' => $raceWamid,
                                'status' => 'delivered',
                                'recipient_id' => '201055556666',
                                'timestamp' => (string) now()->timestamp,
                            ]],
                        ],
                    ]],
                ]],
            ])->assertOk();

            return Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => $raceWamid]],
            ], 200);
        });

        $job = new \App\Jobs\SendWhatsAppTicketImageJob($ticket->id);
        app()->call([$job, 'handle']);

        $ticket->refresh();
        $this->assertEquals('delivered', $ticket->delivery_status, 'A confirmed delivered status must not be downgraded to sent by the job.');
        $this->assertTrue((bool) $ticket->whatsapp_sent);
        $this->assertEquals($raceWamid, $ticket->whatsapp_message_id);
        $this->assertNotNull($ticket->last_delivered_at);
        $this->assertDatabaseMissing('whatsapp_message_status_events', ['message_id' => $raceWamid]);
    }


    public function test_template_webhook_arriving_before_acceptance_is_reconciled_by_wamid()
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'قالب سريع',
            'phone' => '01055556666',
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'sending',
            'reference_code' => 'SRX-TEMPLATE-RACE',
        ]);
        $raceWamid = 'wamid.template_race_fast';

        Http::fake(function () use ($raceWamid) {
            $this->postJson('/webhook/whatsapp', [
                'entry' => [[
                    'changes' => [[
                        'value' => [
                            'statuses' => [[
                                'id' => $raceWamid,
                                'status' => 'delivered',
                                'recipient_id' => '201055556666',
                                'timestamp' => (string) now()->timestamp,
                            ]],
                        ],
                    ]],
                ]],
            ])->assertOk();

            return Http::response(['messages' => [['id' => $raceWamid]]], 200);
        });

        $job = new \App\Jobs\SendWhatsAppTicketTemplateJob('01055556666', $booking->id);
        app()->call([$job, 'handle']);

        $booking->refresh();
        $this->assertSame($raceWamid, $booking->template_message_id);
        $this->assertSame('delivered', $booking->template_status);
        $this->assertDatabaseMissing('whatsapp_message_status_events', ['message_id' => $raceWamid]);
    }

    /**
     * Regression Test 7: Delayed 'sent' webhook cannot downgrade an already 'delivered' ticket.
     */
    public function test_delayed_sent_webhook_cannot_downgrade_delivered_state()
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'هاني شاكر',
            'phone' => '01233445566',
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'reference_code' => 'SRX-ORDER-01',
        ]);

        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'هاني شاكر',
            'phone' => '01233445566',
            'ticket_code' => 'TICK-ORDER-1',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/order.jpg',
            'whatsapp_sent' => true,
            'delivery_status' => 'delivered',
            'whatsapp_message_id' => 'wamid.HBgLMjAx_delivered_first',
            'last_delivered_at' => now()->subMinutes(5),
        ]);

        // Out-of-order 'sent' status webhook arrives later
        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'statuses' => [
                                    [
                                        'id' => 'wamid.HBgLMjAx_delivered_first',
                                        'status' => 'sent',
                                        'recipient_id' => '201233445566',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $res = $this->postJson('/webhook/whatsapp', $payload);
        $res->assertStatus(200);

        $ticket->refresh();
        $this->assertEquals('delivered', $ticket->delivery_status, 'Delayed sent callback must never overwrite confirmed delivered state.');
    }

    /**
     * Regression Test 8: 'read' status webhook marks ticket delivered and preserves receipt.
     */
    public function test_read_webhook_marks_delivered_and_does_not_downgrade()
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'منى زكي',
            'phone' => '01122233344',
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'reference_code' => 'SRX-READ-01',
        ]);

        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'منى زكي',
            'phone' => '01122233344',
            'ticket_code' => 'TICK-READ-1',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/read.jpg',
            'whatsapp_sent' => true,
            'delivery_status' => 'sent',
            'whatsapp_message_id' => 'wamid.HBgLMjAx_read_test',
        ]);

        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'statuses' => [
                                    [
                                        'id' => 'wamid.HBgLMjAx_read_test',
                                        'status' => 'read',
                                        'recipient_id' => '201122233344',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $res = $this->postJson('/webhook/whatsapp', $payload);
        $res->assertStatus(200);

        $ticket->refresh();
        $this->assertEquals('read', $ticket->delivery_status);
        $this->assertTrue($ticket->isTicketDeliveredOrReceived());
    }

    /**
     * Regression Test 9: Unmatched message ID produces diagnostic log and does NOT mutate an arbitrary ticket by phone.
     */
    public function test_unmatched_wamid_does_not_mutate_arbitrary_ticket_by_phone_in_multi_ticket_booking()
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'عمرو دياب',
            'phone' => '01077778888',
            'tickets_count' => 3,
            'total_price' => 450,
            'status' => 'approved',
            'reference_code' => 'SRX-UNMATCH-01',
        ]);

        $t1 = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'تذكرة 1',
            'phone' => '01077778888',
            'ticket_code' => 'TICK-UNMATCH-1',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/u1.jpg',
            'whatsapp_sent' => true,
            'delivery_status' => 'sent',
            'whatsapp_message_id' => 'wamid.exact_ticket_1',
        ]);

        $t2 = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'تذكرة 2',
            'phone' => '01077778888',
            'ticket_code' => 'TICK-UNMATCH-2',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/u2.jpg',
            'whatsapp_sent' => true,
            'delivery_status' => 'sent',
            'whatsapp_message_id' => 'wamid.exact_ticket_2',
        ]);

        // Incoming webhook with unknown wamid but matching phone
        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'statuses' => [
                                    [
                                        'id' => 'wamid.unknown_ghost_id',
                                        'status' => 'failed',
                                        'recipient_id' => '201077778888',
                                        'errors' => [['code' => 131026, 'message' => 'Not on WhatsApp']],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $res = $this->postJson('/webhook/whatsapp', $payload);
        $res->assertStatus(200);

        // Neither ticket should be mutated to failed!
        $this->assertEquals('sent', $t1->fresh()->delivery_status);
        $this->assertEquals('sent', $t2->fresh()->delivery_status);
    }

    /**
     * Regression Test 10: historical migration does not infer delivery from an ambiguous timestamp.
     */
    public function test_historical_reconciliation_migration_reconciles_delivered_sent_tickets()
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'توفيق الحكيم',
            'phone' => '01299990000',
            'tickets_count' => 2,
            'total_price' => 300,
            'status' => 'approved',
            'reference_code' => 'SRX-MIGRATE-01',
        ]);

        $t1 = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'تذكرة 1',
            'phone' => '01299990000',
            'ticket_code' => 'TICK-MIG-1',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/m1.jpg',
            'whatsapp_sent' => true,
            'delivery_status' => 'sent',
            'last_delivered_at' => now()->subDay(),
        ]);

        $t2 = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'تذكرة 2',
            'phone' => '01299990000',
            'ticket_code' => 'TICK-MIG-2',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/m2.jpg',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
            'last_delivered_at' => null,
        ]);

        // Run reconciliation migration
        $migration = require database_path('migrations/2026_10_21_000001_reconcile_delivered_whatsapp_tickets_status.php');
        $migration->up();

        $this->assertEquals('sent', $t1->fresh()->delivery_status);
        $this->assertEquals('pending', $t2->fresh()->delivery_status);
    }

    /**
     * Regression Test 11: Resending a previously delivered ticket after 24h when Meta returns
     * asynchronous 131047 via webhook automatically sends the template, resets the ticket to
     * 'بانتظار نقر العميل', and delivers the QR image once the customer clicks the new template.
     */
    public function test_async_131047_webhook_on_delivered_ticket_auto_sends_template_and_resets_to_awaiting_click()
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'عمرو دياب',
            'phone' => '01055556666',
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'reference_code' => 'SRX-ASYNC-131047',
            'template_sent' => true,
            'template_status' => 'delivered',
            'template_message_id' => 'wamid.OLD_TPL_1',
        ]);

        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'تذكرة عمرو',
            'phone' => '01055556666',
            'ticket_code' => 'TICK-ASYNC-24H',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/async24h.jpg',
            'whatsapp_sent' => true,
            'delivery_status' => 'delivered',
            'whatsapp_message_id' => 'wamid.OLD_IMG_1',
            'claimed_at' => now()->subDays(4),
            'last_delivered_at' => now()->subDays(4),
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::sequence()
                // 1) Admin clicks Resend -> Meta accepts sendImage HTTP call with a new wamid
                ->push(['messages' => [['id' => 'wamid.RESEND_IMG_24H']]], 200)
                // 2) Async 131047 webhook arrives -> system auto-sends template
                ->push(['messages' => [['id' => 'wamid.NEW_TPL_24H']]], 200)
                // 3) Customer clicks the new template CTA -> system sends the QR image
                ->push(['messages' => [['id' => 'wamid.FINAL_IMG_DELIV']]], 200),
        ]);

        // Step 1: Admin clicks resend on the previously delivered ticket
        $res = $this->actingAs($this->admin)->post(route('admin.resend.ticket', $ticket->id));
        $res->assertRedirect();
        $this->assertEquals('wamid.RESEND_IMG_24H', $ticket->fresh()->whatsapp_message_id);

        // Step 2: Meta sends async status webhook with 131047 (24-hour window closed)
        $webhook131047 = [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'statuses' => [[
                            'id' => 'wamid.RESEND_IMG_24H',
                            'status' => 'failed',
                            'timestamp' => (string) now()->timestamp,
                            'errors' => [[
                                'code' => 131047,
                                'title' => 'Re-engagement message',
                                'message' => 'Re-engagement message (more than 24 hours have passed since the customer last replied)',
                            ]],
                        ]],
                    ],
                ]],
            ]],
        ];

        $this->postJson('/webhook/whatsapp', $webhook131047)->assertStatus(200);

        // Verify ticket & booking automatically transitioned to 'بانتظار نقر العميل'
        $ticket->refresh();
        $booking->refresh();

        $this->assertEquals('pending', $ticket->delivery_status);
        $this->assertFalse((bool) $ticket->whatsapp_sent);
        $this->assertNull($ticket->claimed_at);
        $this->assertNull($ticket->delivery_error);
        $this->assertEquals('wamid.NEW_TPL_24H', $booking->template_message_id);
        $this->assertTrue($booking->isAwaitingCustomerClick());
        $this->assertEquals('بانتظار نقر العميل', $booking->delivery_badge['text']);

        // Also check pollStatus returns updated delivery_html with 'بانتظار نقر العميل'
        $poll = $this->actingAs($this->admin)->getJson(route('admin.bookings.poll-status', ['booking_id' => $booking->id]));
        $poll->assertOk();
        $this->assertStringContainsString('بانتظار نقر العميل', $poll->json('booking.delivery_html'));

        // Step 3: Customer clicks 'استلام التذكرة' on the newly sent template
        $ctaPayload = [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messages' => [[
                            'from' => '201055556666',
                            'id' => 'wamid.INBOUND_CLICK_24H',
                            'type' => 'button',
                            'context' => ['id' => 'wamid.NEW_TPL_24H'],
                            'button' => ['text' => 'استلام التذكرة', 'payload' => 'استلام التذكرة'],
                        ]],
                    ],
                ]],
            ]],
        ];

        $this->postJson('/webhook/whatsapp', $ctaPayload)->assertStatus(200);

        $ticket->refresh();
        $this->assertEquals('sent', $ticket->delivery_status);
        $this->assertTrue((bool) $ticket->whatsapp_sent);
        $this->assertEquals('wamid.FINAL_IMG_DELIV', $ticket->whatsapp_message_id);

        // Step 4: Meta confirms 'delivered' for the QR image
        $deliveredPayload = [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'statuses' => [[
                            'id' => 'wamid.FINAL_IMG_DELIV',
                            'status' => 'delivered',
                            'timestamp' => (string) now()->timestamp,
                        ]],
                    ],
                ]],
            ]],
        ];

        $this->postJson('/webhook/whatsapp', $deliveredPayload)->assertStatus(200);

        $ticket->refresh();
        $booking->refresh();
        $this->assertEquals('delivered', $ticket->delivery_status);
        $this->assertEquals('تم الاستلام (1/1)', $booking->delivery_badge['text']);
    }

    /**
     * Regression Test 12: Template job failure (e.g. 131026 phone not on WhatsApp) preserves
     * formatted Arabic error on booking AND marks waiting tickets as failed with reason.
     */
    public function test_template_job_failure_preserves_arabic_error_and_marks_outer_and_inner_status_failed()
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'عميل رقم غير مسجل',
            'phone' => '01099887766',
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'reference_code' => 'SRX-NO-WA-01',
            'template_sent' => false,
            'template_status' => 'sending',
        ]);

        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'تذكرة 1',
            'phone' => '01099887766',
            'ticket_code' => 'TICK-NO-WA-1',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1234/nowa.jpg',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => 'Message undeliverable',
                    'code' => 131026,
                ],
            ], 400),
        ]);

        $job = new \App\Jobs\SendWhatsAppTicketTemplateJob($booking->phone, $booking->id);
        try {
            $job->handle(app(\App\Services\TicketDeliveryService::class));
        } catch (\Throwable $e) {
            $job->failed($e);
        }

        $booking->refresh();
        $ticket->refresh();

        $this->assertEquals('failed', $booking->template_status);
        $this->assertStringContainsString('رقم الهاتف غير مسجل على WhatsApp', (string) $booking->template_error);
        $this->assertEquals('فشل الإرسال', $booking->delivery_badge['text']);

        $this->assertEquals('failed', $ticket->delivery_status);
        $this->assertStringContainsString('رقم الهاتف غير مسجل على WhatsApp', (string) $ticket->delivery_error);
    }
}
