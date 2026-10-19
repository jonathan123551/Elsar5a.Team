<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Show;
use App\Models\ShowTime;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminBookingsAndResendTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Show $show;
    private ShowTime $showTime;

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
            'whatsapp_sent' => true,
            'delivery_status' => 'sent',
            'last_delivered_at' => now(),
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

        // Visual delivery lifecycle timeline
        $response->assertSee('مسار ومراحل دورة الحجز والتسليم');
        $response->assertSee('تسجيل طلب الحجز');
        $response->assertSee('تحويل المبلغ وإرفاق الإثبات');
        $response->assertSee('اعتماد الإدارة');
        $response->assertSee('إرسال قالب الاستلام الرسمي عبر واتساب');

        // Ticket stub and error details
        $response->assertSee('TICK-SM-001');
        $response->assertSee('تعذر الوصول لرقم الواتساب');
        $response->assertSee('إعادة إرسال التذكرة');

        // Floating action dock with bulk resend
        $response->assertSee('إعادة إرسال التذاكر');
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
        $this->assertNotNull($ticket->last_delivered_at);
        $this->assertNotNull($ticket->resend_requested_at);
        $this->assertNull($ticket->delivery_error);
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
            'template_status' => 'pending',
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
        $this->assertStringContainsString('نافذة الـ 24 ساعة مغلقة', (string) $ticket->delivery_error);
        $this->assertEquals('sent', $booking->template_status);
        $this->assertNotNull($booking->template_sent_at);
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
            'delivery_status' => 'sent',
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
        $this->assertEquals('sent', $ticket->delivery_status);
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
        $response->assertSessionHas('status', '✅ تم إرسال 2 تذكرة بنجاح عبر واتساب.');

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
}
