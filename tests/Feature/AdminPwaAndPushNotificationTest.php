<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppTicketTemplateJob;
use App\Models\AdminNotification;
use App\Models\AdminNotificationPreference;
use App\Models\Booking;
use App\Models\PushSubscription;
use App\Models\Show;
use App\Models\ShowTime;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketDeliveryService;
use App\Services\WebPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminPwaAndPushNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Show $show;
    protected ShowTime $showTime;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'elsar5ateam2026@gmail.com'],
            ['name' => 'Admin', 'password' => bcrypt('password')]
        );

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
     * 1. PWA manifest availability & Admin-only scope.
     */
    public function test_pwa_manifest_is_available_and_restricted_to_admin_only(): void
    {
        $response = $this->get('/admin/manifest.webmanifest');

        $response->assertStatus(200);
        $this->assertStringContainsString('application/manifest+json', (string) $response->headers->get('Content-Type'));

        $manifest = $response->json();
        $this->assertEquals('Elsar5a Admin', $manifest['name']);
        $this->assertEquals('Elsar5a', $manifest['short_name']);
        $this->assertEquals('standalone', $manifest['display']);
        $this->assertEquals('/admin', $manifest['start_url']);
        $this->assertEquals('/admin', $manifest['scope']);
        $this->assertEquals('#070a11', $manifest['theme_color']);
        $this->assertNotEmpty($manifest['icons']);

        // Admin layout includes manifest link and push opt-in button
        $adminPage = $this->actingAs($this->admin)->get('/admin');
        $adminPage->assertStatus(200);
        $adminPage->assertSee('/admin/manifest.webmanifest', false);
        $adminPage->assertSee('تفعيل إشعارات الحجوزات');

        // Customer homepage does NOT include Admin PWA manifest
        $homePage = $this->get('/');
        $homePage->assertStatus(200);
        $homePage->assertDontSee('/admin/manifest.webmanifest', false);
    }

    /**
     * 2. Service worker availability & safe caching rules.
     */
    public function test_service_worker_is_available_and_never_caches_sensitive_admin_pages(): void
    {
        $response = $this->get('/admin/sw.js');

        $response->assertStatus(200);
        $this->assertStringContainsString('javascript', (string) $response->headers->get('Content-Type'));
        $this->assertEquals('/admin', $response->headers->get('Service-Worker-Allowed'));

        $swContent = $response->getContent();
        $this->assertStringContainsString("addEventListener('install'", $swContent);
        $this->assertStringContainsString("addEventListener('activate'", $swContent);
        $this->assertStringContainsString("addEventListener('push'", $swContent);
        $this->assertStringContainsString("addEventListener('notificationclick'", $swContent);
        $this->assertStringContainsString('/images/pwa/admin-icon-192.png', $swContent);
        $this->assertStringContainsString("request.mode === 'navigate'", $swContent);
    }

    /**
     * 3 & 5. Authenticated Admin can register multiple push subscriptions & deduplicate endpoints.
     */
    public function test_authenticated_admin_can_register_multiple_push_subscriptions_and_unsubscribe(): void
    {
        // Device 1: Android Chrome
        $res1 = $this->actingAs($this->admin)->postJson(route('admin.push-subscriptions.store'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/device-android-1',
            'keys' => [
                'p256dh' => 'BNcRdreALRFXTkOOUHK1EtK2wtaz5Ry4YfYCA_0QTpQtUbVlUls0VJXg7A8u-Ts1XbjhazAkj7I99e8QcYP7DkM',
                'auth' => 'tBHItJI5svbpez7KI4CCXg',
            ],
            'device_label' => 'Android Chrome',
        ]);

        $res1->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('message', 'تم تفعيل الإشعارات بنجاح ✓');

        // Device 2: iPhone Home Screen PWA
        $res2 = $this->actingAs($this->admin)->postJson(route('admin.push-subscriptions.store'), [
            'endpoint' => 'https://web.push.apple.com/Q1w2e3r4-iphone-pwa',
            'keys' => [
                'p256dh' => 'BOr1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef',
                'auth' => 'a1b2c3d4e5f6g7h8i9j0k1',
            ],
            'device_label' => 'iPhone PWA',
        ]);

        $res2->assertStatus(200)->assertJsonPath('ok', true);

        $this->assertEquals(2, PushSubscription::where('user_id', $this->admin->id)->count());

        // Re-registering same endpoint updates existing record instead of duplicating
        $this->actingAs($this->admin)->postJson(route('admin.push-subscriptions.store'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/device-android-1',
            'keys' => [
                'p256dh' => 'UPDATED_KEY_VALUE',
                'auth' => 'UPDATED_AUTH_VALUE',
            ],
        ])->assertStatus(200);

        $this->assertEquals(2, PushSubscription::where('user_id', $this->admin->id)->count());
        $this->assertEquals(
            'UPDATED_KEY_VALUE',
            PushSubscription::where('endpoint', 'https://fcm.googleapis.com/fcm/send/device-android-1')->value('public_key')
        );

        // Unsubscribe Device 1
        $this->actingAs($this->admin)->postJson(route('admin.push-subscriptions.unsubscribe'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/device-android-1',
        ])->assertStatus(200)->assertJsonPath('ok', true);

        $this->assertEquals(1, PushSubscription::where('user_id', $this->admin->id)->count());
    }

    /**
     * 4. Unauthenticated user and non-admin user cannot register an Admin push subscription.
     */
    public function test_unauthenticated_and_non_admin_users_cannot_register_push_subscription(): void
    {
        $guestRes = $this->post(route('admin.push-subscriptions.store'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/guest-attempt',
            'keys' => ['p256dh' => 'key', 'auth' => 'auth'],
        ]);

        $guestRes->assertRedirect(route('login'));
        $this->assertEquals(0, PushSubscription::count());

        $nonAdmin = User::create([
            'name' => 'Customer',
            'email' => 'customer@example.com',
            'password' => bcrypt('password'),
        ]);

        $nonAdminRes = $this->actingAs($nonAdmin)->post(route('admin.push-subscriptions.store'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/non-admin-attempt',
            'keys' => ['p256dh' => 'key', 'auth' => 'auth'],
        ]);

        $nonAdminRes->assertStatus(403);
        $this->assertEquals(0, PushSubscription::count());
    }

    /**
     * 6 & 7. New booking and payment proof create Admin notifications and trigger Web Push.
     */
    public function test_new_booking_and_payment_proof_create_admin_notifications_and_send_web_push(): void
    {
        $webPush = app(WebPushService::class);
        $subKeys = $webPush->generateVapidKeys();
        $details = openssl_pkey_get_details(openssl_pkey_get_private($subKeys['private_key_pem']));
        $p256dh = WebPushService::base64UrlEncode(
            "\x04" . str_pad($details['ec']['x'], 32, "\x00", STR_PAD_LEFT) . str_pad($details['ec']['y'], 32, "\x00", STR_PAD_LEFT)
        );
        $authSecret = WebPushService::base64UrlEncode(random_bytes(16));

        PushSubscription::registerForUser($this->admin->id, [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/admin-phone-1',
            'public_key' => $p256dh,
            'auth_token' => $authSecret,
        ]);

        Http::fake([
            'fcm.googleapis.com/*' => Http::response('', 201),
        ]);

        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'أحمد زكي',
            'phone' => '201012345678',
            'tickets_count' => 2,
            'total_price' => 300,
            'transfer_screenshot_path' => 'https://res.cloudinary.com/demo/image/upload/v1/proof_25.jpg',
            'status' => 'pending',
            'reference_code' => 'SRC-NEW-BOOKING-25',
        ]);

        // Assert New Booking notification
        $newBookingNotif = AdminNotification::where('booking_id', $booking->id)
            ->where('type', 'new_booking')
            ->first();

        $this->assertNotNull($newBookingNotif);
        $this->assertEquals("🔔 حجز جديد #{$booking->id}", $newBookingNotif->title);
        $this->assertEquals('تم إنشاء حجز جديد ويحتاج إلى مراجعة.', $newBookingNotif->body);
        $this->assertEquals("/admin/bookings/{$booking->id}", $newBookingNotif->target_url);
        $this->assertNotNull($newBookingNotif->push_sent_at);

        // Assert Payment Proof notification
        $proofNotif = AdminNotification::where('booking_id', $booking->id)
            ->where('type', 'payment_proof_uploaded')
            ->first();

        $this->assertNotNull($proofNotif);
        $this->assertEquals('💳 إثبات تحويل جديد', $proofNotif->title);
        $this->assertEquals("تم رفع إثبات تحويل للحجز #{$booking->id} ويحتاج إلى مراجعة.", $proofNotif->body);
        $this->assertEquals("/admin/bookings/{$booking->id}", $proofNotif->target_url);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'fcm.googleapis.com/fcm/send/admin-phone-1')
            && str_starts_with((string) $request->header('Authorization')[0], 'vapid t='));
    }

    /**
     * 8. WhatsApp template failure and ticket delivery failure create Admin notifications safely.
     */
    public function test_whatsapp_template_and_ticket_failures_create_safe_admin_notifications(): void
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'منى زكي',
            'phone' => '201099887766',
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'sending',
            'reference_code' => 'SRC-WA-FAIL-01',
        ]);

        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'تذكرة منى',
            'phone' => '201099887766',
            'ticket_code' => 'TIC-MONA-01',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1/qr1.jpg',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => 'Message undeliverable Bearer EAAGSecretToken12345',
                    'code' => 131026,
                ],
            ], 400),
        ]);

        $job = new SendWhatsAppTicketTemplateJob($booking->phone, $booking->id);
        try {
            $job->handle(app(TicketDeliveryService::class));
        } catch (\Throwable $e) {
            $job->failed($e);
        }

        $tplFailNotif = AdminNotification::where('booking_id', $booking->id)
            ->where('type', 'whatsapp_template_failed')
            ->first();

        $this->assertNotNull($tplFailNotif);
        $this->assertEquals('⚠️ فشل إرسال WhatsApp', $tplFailNotif->title);
        $this->assertStringContainsString("فشل إرسال قالب الاستلام للحجز #{$booking->id}.", $tplFailNotif->body);
        $this->assertStringNotContainsString('EAAGSecretToken12345', $tplFailNotif->body);
        $this->assertEquals("/admin/bookings/{$booking->id}", $tplFailNotif->target_url);

        // Now test individual ticket delivery failure
        $ticket->update(['whatsapp_message_id' => 'wamid.TICKET_FAIL_1', 'delivery_status' => 'sent', 'whatsapp_sent' => true]);
        $ticket->markWhatsAppFailed('تعذر تحميل الوسائط Bearer EAAGSecretToken999');

        $ticketFailNotif = AdminNotification::where('booking_id', $booking->id)
            ->where('type', 'ticket_delivery_failed')
            ->first();

        $this->assertNotNull($ticketFailNotif);
        $this->assertEquals('❌ فشل تسليم التذكرة', $ticketFailNotif->title);
        $this->assertStringContainsString("فشل تسليم إحدى تذاكر الحجز #{$booking->id}.", $ticketFailNotif->body);
        $this->assertStringNotContainsString('EAAGSecretToken999', $ticketFailNotif->body);
        $this->assertEquals("/admin/bookings/{$booking->id}", $ticketFailNotif->target_url);
    }

    /**
     * 9 & 10. Ticket claim + Meta-confirmed delivery create notifications, and duplicate webhooks do NOT duplicate.
     */
    public function test_ticket_delivery_creates_notification_only_on_confirmed_delivery_and_deduplicates_webhooks(): void
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'كريم عبد العزيز',
            'phone' => '201055443322',
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_sent' => true,
            'template_status' => 'delivered',
            'template_message_id' => 'wamid.TPL_KAREEM_1',
            'reference_code' => 'SRC-KAREEM-01',
        ]);

        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'كريم عبد العزيز',
            'phone' => '201055443322',
            'ticket_code' => 'TIC-KAR-01',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1/qr_kar.jpg',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.IMG_KAREEM_1']],
            ], 200),
        ]);

        // Customer clicks 'استلام التذكرة'
        $this->postJson('/webhook/whatsapp', [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messages' => [[
                            'from' => '201055443322',
                            'id' => 'wamid.INBOUND_CTA_1',
                            'type' => 'button',
                            'context' => ['id' => 'wamid.TPL_KAREEM_1'],
                            'button' => ['text' => 'استلام التذكرة', 'payload' => 'استلام التذكرة'],
                        ]],
                    ],
                ]],
            ]],
        ])->assertStatus(200);

        // Claim notification created, but NOT 'ticket_delivered' yet (HTTP 200 is only 'sent')
        $claimNotif = AdminNotification::where('booking_id', $booking->id)->where('type', 'ticket_claimed')->first();
        $this->assertNotNull($claimNotif);
        $this->assertEquals('📲 العميل طلب استلام التذكرة', $claimNotif->title);
        $this->assertEquals("الحجز #{$booking->id} بدأ إرسال التذاكر.", $claimNotif->body);

        $this->assertEquals(0, AdminNotification::where('booking_id', $booking->id)->where('type', 'ticket_delivered')->count());

        // Meta sends 'sent' webhook -> still NOT delivered
        $this->postJson('/webhook/whatsapp', [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'statuses' => [[
                            'id' => 'wamid.IMG_KAREEM_1',
                            'status' => 'sent',
                            'timestamp' => (string) now()->timestamp,
                        ]],
                    ],
                ]],
            ]],
        ])->assertStatus(200);

        $this->assertEquals(0, AdminNotification::where('booking_id', $booking->id)->where('type', 'ticket_delivered')->count());

        // Meta sends 'delivered' webhook -> creates ticket_delivered AND booking_completed
        $this->postJson('/webhook/whatsapp', [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'statuses' => [[
                            'id' => 'wamid.IMG_KAREEM_1',
                            'status' => 'delivered',
                            'timestamp' => (string) now()->timestamp,
                        ]],
                    ],
                ]],
            ]],
        ])->assertStatus(200);

        $deliveredNotif = AdminNotification::where('booking_id', $booking->id)->where('type', 'ticket_delivered')->first();
        $this->assertNotNull($deliveredNotif);
        $this->assertEquals('🎟️ تم تسليم التذاكر', $deliveredNotif->title);
        $this->assertEquals("تم تسليم جميع تذاكر الحجز #{$booking->id}.", $deliveredNotif->body);

        $completedNotif = AdminNotification::where('booking_id', $booking->id)->where('type', 'booking_completed')->first();
        $this->assertNotNull($completedNotif);
        $this->assertEquals("✅ الحجز #{$booking->id} مكتمل", $completedNotif->title);
        $this->assertEquals('تم إرسال واستلام جميع تذاكر الحجز بنجاح.', $completedNotif->body);

        $countAfterFirstDelivered = AdminNotification::where('booking_id', $booking->id)->count();

        // Duplicate 'delivered' webhook + subsequent 'read' webhook must NOT create duplicate notifications
        $this->postJson('/webhook/whatsapp', [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'statuses' => [[
                            'id' => 'wamid.IMG_KAREEM_1',
                            'status' => 'delivered',
                            'timestamp' => (string) now()->timestamp,
                        ]],
                    ],
                ]],
            ]],
        ])->assertStatus(200);

        $this->postJson('/webhook/whatsapp', [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'statuses' => [[
                            'id' => 'wamid.IMG_KAREEM_1',
                            'status' => 'read',
                            'timestamp' => (string) now()->timestamp,
                        ]],
                    ],
                ]],
            ]],
        ])->assertStatus(200);

        $this->assertEquals(1, AdminNotification::where('booking_id', $booking->id)->where('type', 'ticket_delivered')->count());
        $this->assertEquals(1, AdminNotification::where('booking_id', $booking->id)->where('type', 'booking_completed')->count());
        $this->assertEquals($countAfterFirstDelivered, AdminNotification::where('booking_id', $booking->id)->count());
    }

    /**
     * 11 & 12. Notification click destination, mark-as-read, preferences, and expired 410 subscription cleanup.
     */
    public function test_notification_click_opens_admin_booking_marks_read_and_respects_preferences(): void
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'عادل إمام',
            'phone' => '201011112222',
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'pending',
            'reference_code' => 'SRC-ADEL-01',
        ]);

        $notification = AdminNotification::where('booking_id', $booking->id)
            ->where('type', 'new_booking')
            ->firstOrFail();

        $this->assertTrue($notification->isUnread());

        // Clicking / opening the notification marks it read and redirects to /admin/bookings/{booking}
        $openRes = $this->actingAs($this->admin)->get(route('admin.notifications.open', $notification));
        $openRes->assertRedirect("/admin/bookings/{$booking->id}");

        $this->assertFalse($notification->fresh()->isUnread());

        // Target URL sanitization prevents redirecting to customer or external pages
        $sanitized = AdminNotification::sanitizeTargetUrl('https://evil.example.com/phish', $booking->id);
        $this->assertEquals("/admin/bookings/{$booking->id}", $sanitized);

        $sanitizedCustomer = AdminNotification::sanitizeTargetUrl('/shows/1/book', $booking->id);
        $this->assertEquals("/admin/bookings/{$booking->id}", $sanitizedCustomer);

        // Disable 'new_bookings' preference -> next booking should not create 'new_booking' notification
        $this->actingAs($this->admin)->postJson(route('admin.notifications.preferences.update'), [
            'new_bookings' => false,
            'payment_proofs' => true,
            'whatsapp_failures' => true,
            'ticket_delivery' => true,
            'completed_bookings' => true,
        ])->assertStatus(200)->assertJsonPath('preferences.new_bookings', false);

        $booking2 = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'حجز بدون تنبيه',
            'phone' => '201033334444',
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'pending',
            'reference_code' => 'SRC-MUTED-02',
        ]);

        $this->assertEquals(
            0,
            AdminNotification::where('booking_id', $booking2->id)->where('type', 'new_booking')->count()
        );

        // Expired push subscription (HTTP 410 Gone) is automatically cleaned up
        $staleSub = PushSubscription::registerForUser($this->admin->id, [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/expired-endpoint-410',
            'public_key' => 'stale-key',
            'auth_token' => 'stale-auth',
        ]);

        Http::fake([
            'fcm.googleapis.com/*' => Http::response('Gone', 410),
        ]);

        app(WebPushService::class)->sendToUser($this->admin->id, ['title' => 'Test', 'body' => 'Body']);
        $this->assertDatabaseMissing('push_subscriptions', ['id' => $staleSub->id]);

        // 12. Existing Admin polling and Notification Center mark-all-as-read work
        $pollRes = $this->actingAs($this->admin)->getJson(route('admin.bookings.poll-status', [
            'booking_id' => $booking->id,
            'index' => 1,
        ]));
        $pollRes->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('booking.id', $booking->id);

        $centerRes = $this->actingAs($this->admin)->get(route('admin.notifications.index'));
        $centerRes->assertStatus(200)->assertSee('مركز الإشعارات');

        $this->actingAs($this->admin)->postJson(route('admin.notifications.read-all'))
            ->assertStatus(200)
            ->assertJsonPath('unread_count', 0);
    }
}
