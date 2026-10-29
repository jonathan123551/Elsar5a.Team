<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppTicketImageJob;
use App\Models\Booking;
use App\Models\Show;
use App\Models\ShowTime;
use App\Models\Ticket;
use App\Models\WhatsAppTemplateMessage;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * WhatsApp CTA Claim & Multi-Holder Multi-Booking Correlation Tests.
 *
 * Covers:
 * 1. Multiple bookings, same customer -> all eligible undelivered tickets dispatched.
 * 2. Some tickets already delivered -> only undelivered tickets dispatched.
 * 3. Multiple holders in one booking -> only clicking customer's tickets dispatched.
 * 4. Multiple bookings + multiple holders -> only clicking customer's tickets dispatched across all bookings.
 * 5. Duplicate CTA -> no duplicate QR sends.
 * 6. WAMID overwritten scenario -> resolves customer and dispatches tickets without ambiguous booking error.
 */
class WhatsAppCtaMultiHolderCorrelationTest extends TestCase
{
    use RefreshDatabase;

    private ShowTime $showTime;

    private const BOOKER = '201274663229';   // مريم — booker
    private const HOLDER = '201222356357';   // Jonathan
    private const HOLDER_A = '201273192129'; // Holder A
    private const HOLDER_B = '201211151781'; // Holder B
    private const TPL_HOLDER = 'wamid.TPL_445_JONATHAN';

    protected function setUp(): void
    {
        parent::setUp();

        $show = Show::create([
            'title' => 'عرض',
            'description' => 'وصف',
            'is_active' => true,
            'ticket_price' => 150,
            'ticket_template_path' => 'https://res.cloudinary.com/demo/image/upload/template.png',
            'ticket_qr_x' => 100,
            'ticket_qr_y' => 100,
            'ticket_qr_size' => 200,
        ]);

        $this->showTime = ShowTime::create([
            'show_id' => $show->id,
            'date' => now()->addDays(5)->format('Y-m-d'),
            'time' => '19:00:00',
            'hall_name' => 'مسرح',
            'ticket_price' => 150,
            'available_tickets' => 50,
            'total_tickets' => 50,
            'is_sold_out' => false,
        ]);
    }

    /** @return array{0: Booking, 1: Ticket, 2: Ticket} */
    private function makeBooking445(string $holderPhoneFormat = self::HOLDER, string $ref = 'SRC-445'): array
    {
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'الفنانه مريم محسن',
            'phone' => self::BOOKER,
            'tickets_count' => 2,
            'total_price' => 300,
            'status' => 'approved',
            'template_sent' => true,
            'template_status' => 'delivered',
            'template_message_id' => self::TPL_HOLDER,
            'reference_code' => $ref,
        ]);

        $bookerTicket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'الفنانه مريم محسن',
            'phone' => self::BOOKER,
            'ticket_code' => $ref . '-T1',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1/qr_1.jpg',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        $holderTicket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'Jonathan Maged',
            'phone' => $holderPhoneFormat,
            'ticket_code' => $ref . '-T2',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1/qr_2.jpg',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        return [$booking, $bookerTicket, $holderTicket];
    }

    private function tap(string $from, ?string $contextId, string $inboundId): \Illuminate\Testing\TestResponse
    {
        $message = [
            'from' => $from,
            'id' => $inboundId,
            'type' => 'button',
            'button' => ['text' => 'استلام التذكرة', 'payload' => 'استلام التذكرة'],
        ];
        if ($contextId) {
            $message['context'] = ['from' => '15550000000', 'id' => $contextId];
        }

        return $this->postJson('/webhook/whatsapp', [
            'entry' => [['changes' => [['value' => ['messages' => [$message]]]]]],
        ]);
    }

    /* ---------- Phone canonicalization ---------- */

    public function test_egyptian_phone_formats_resolve_to_same_whatsapp_identity(): void
    {
        foreach (['01222356357', '1222356357', '201222356357', '+201222356357', '00201222356357', '+20 122 235 6357'] as $input) {
            $this->assertSame('201222356357', PhoneNumber::canonical($input), $input);
        }
        $this->assertTrue(PhoneNumber::same('01222356357', '+201222356357'));
        $this->assertFalse(PhoneNumber::same('01222356357', '01274663229'));
        $this->assertFalse(PhoneNumber::same('', ''));
    }

    public function test_secondary_ticket_holder_cta_resolves_booking_by_context_and_dispatches_qr_job(): void
    {
        Queue::fake();
        [$booking, $bookerTicket, $holderTicket] = $this->makeBooking445();

        $this->tap(self::HOLDER, self::TPL_HOLDER, 'wamid.IN_445_1')
            ->assertOk()
            ->assertJsonMissing(['status' => 'context_phone_mismatch']);

        Queue::assertPushed(SendWhatsAppTicketImageJob::class, 1);
        Queue::assertPushed(SendWhatsAppTicketImageJob::class, fn ($job) => $job->ticketId === $holderTicket->id);

        $this->assertNotNull($holderTicket->fresh()->claimed_at);
        $this->assertNull($bookerTicket->fresh()->claimed_at);
    }

    public function test_secondary_holder_matches_when_ticket_phone_stored_in_local_format(): void
    {
        Queue::fake();
        [, , $holderTicket] = $this->makeBooking445('01222356357');

        $this->tap('+20 122 235 6357', self::TPL_HOLDER, 'wamid.IN_445_LOCAL')->assertOk();

        Queue::assertPushed(SendWhatsAppTicketImageJob::class, fn ($job) => $job->ticketId === $holderTicket->id);
    }

    public function test_secondary_holder_cta_end_to_end_sends_qr_and_marks_ticket_accepted(): void
    {
        [, $bookerTicket, $holderTicket] = $this->makeBooking445();

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.IMG_445_JONATHAN']]], 200),
        ]);

        $this->tap(self::HOLDER, self::TPL_HOLDER, 'wamid.IN_445_E2E')->assertOk();

        $holderTicket->refresh();
        $this->assertSame('wamid.IMG_445_JONATHAN', $holderTicket->whatsapp_message_id);
        $this->assertNotSame('pending', $holderTicket->delivery_status);
        $this->assertNull($bookerTicket->fresh()->whatsapp_message_id);

        Http::assertSent(fn ($r) => str_contains($r->url(), 'graph.facebook.com')
            && data_get($r->data(), 'to') !== null
            && PhoneNumber::same(data_get($r->data(), 'to'), self::HOLDER));
        Http::assertNotSent(fn ($r) => PhoneNumber::same((string) data_get($r->data(), 'to'), self::BOOKER));

        $this->postJson('/webhook/whatsapp', [
            'entry' => [['changes' => [['value' => ['statuses' => [[
                'id' => 'wamid.IMG_445_JONATHAN',
                'status' => 'delivered',
                'recipient_id' => self::HOLDER,
                'timestamp' => (string) now()->timestamp,
            ]]]]]]],
        ])->assertOk();

        $this->assertContains($holderTicket->fresh()->delivery_status, ['delivered', 'read']);
    }

    public function test_context_match_from_phone_outside_booking_is_still_rejected(): void
    {
        Queue::fake();
        [, $bookerTicket, $holderTicket] = $this->makeBooking445();

        $this->tap('201099999999', self::TPL_HOLDER, 'wamid.IN_445_STRANGER')
            ->assertOk()
            ->assertJson(['status' => 'context_phone_mismatch']);

        Queue::assertNothingPushed();
        $this->assertNull($bookerTicket->fresh()->claimed_at);
        $this->assertNull($holderTicket->fresh()->claimed_at);
    }

    /* ---------- Required Test 1: Multiple bookings, same customer ---------- */
    public function test_multiple_bookings_same_customer_dispatches_all_undelivered_tickets(): void
    {
        Queue::fake();

        // 3 approved bookings, each having an undelivered ticket for Jonathan
        $b445 = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'Jonathan',
            'phone' => self::HOLDER,
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'delivered',
            'template_message_id' => 'wamid.TPL_445',
            'reference_code' => 'SRC-445',
        ]);
        $t445 = Ticket::create([
            'booking_id' => $b445->id,
            'name' => 'Jonathan',
            'phone' => self::HOLDER,
            'ticket_code' => 'SRC-445-T1',
            'qr_image_path' => 'https://res.cloudinary.com/demo/qr1.jpg',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        $b446 = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'Jonathan',
            'phone' => self::HOLDER,
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'delivered',
            'template_message_id' => 'wamid.TPL_446',
            'reference_code' => 'SRC-446',
        ]);
        $t446 = Ticket::create([
            'booking_id' => $b446->id,
            'name' => 'Jonathan',
            'phone' => self::HOLDER,
            'ticket_code' => 'SRC-446-T1',
            'qr_image_path' => 'https://res.cloudinary.com/demo/qr2.jpg',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        $b447 = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'Jonathan',
            'phone' => self::HOLDER,
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'delivered',
            'template_message_id' => 'wamid.TPL_447',
            'reference_code' => 'SRC-447',
        ]);
        $t447 = Ticket::create([
            'booking_id' => $b447->id,
            'name' => 'Jonathan',
            'phone' => self::HOLDER,
            'ticket_code' => 'SRC-447-T1',
            'qr_image_path' => 'https://res.cloudinary.com/demo/qr3.jpg',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        // Customer clicks CTA from #447 template
        $this->tap(self::HOLDER, 'wamid.TPL_447', 'wamid.IN_TAP_447')->assertOk();

        // Expected: 3 tickets dispatched across all approved bookings
        Queue::assertPushed(SendWhatsAppTicketImageJob::class, 3);
        Queue::assertPushed(SendWhatsAppTicketImageJob::class, fn ($j) => $j->ticketId === $t445->id);
        Queue::assertPushed(SendWhatsAppTicketImageJob::class, fn ($j) => $j->ticketId === $t446->id);
        Queue::assertPushed(SendWhatsAppTicketImageJob::class, fn ($j) => $j->ticketId === $t447->id);

        $this->assertNotNull($t445->fresh()->claimed_at);
        $this->assertNotNull($t446->fresh()->claimed_at);
        $this->assertNotNull($t447->fresh()->claimed_at);
    }

    /* ---------- Required Test 2: Some tickets already delivered ---------- */
    public function test_some_tickets_already_delivered_only_dispatches_undelivered(): void
    {
        Queue::fake();

        // #445 -> delivered
        $b445 = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'Jonathan',
            'phone' => self::HOLDER,
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'delivered',
            'reference_code' => 'SRC-445',
        ]);
        $t445 = Ticket::create([
            'booking_id' => $b445->id,
            'name' => 'Jonathan',
            'phone' => self::HOLDER,
            'ticket_code' => 'SRC-445-T1',
            'whatsapp_sent' => true,
            'delivery_status' => 'delivered',
        ]);

        // #446 -> undelivered
        $b446 = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'Jonathan',
            'phone' => self::HOLDER,
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'delivered',
            'reference_code' => 'SRC-446',
        ]);
        $t446 = Ticket::create([
            'booking_id' => $b446->id,
            'name' => 'Jonathan',
            'phone' => self::HOLDER,
            'ticket_code' => 'SRC-446-T1',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        // #447 -> delivered
        $b447 = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'Jonathan',
            'phone' => self::HOLDER,
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'delivered',
            'template_message_id' => 'wamid.TPL_447',
            'reference_code' => 'SRC-447',
        ]);
        $t447 = Ticket::create([
            'booking_id' => $b447->id,
            'name' => 'Jonathan',
            'phone' => self::HOLDER,
            'ticket_code' => 'SRC-447-T1',
            'whatsapp_sent' => true,
            'delivery_status' => 'read',
        ]);

        $this->tap(self::HOLDER, 'wamid.TPL_447', 'wamid.IN_TAP')->assertOk();

        // Only #446 ticket should be dispatched
        Queue::assertPushed(SendWhatsAppTicketImageJob::class, 1);
        Queue::assertPushed(SendWhatsAppTicketImageJob::class, fn ($j) => $j->ticketId === $t446->id);
        Queue::assertNotPushed(SendWhatsAppTicketImageJob::class, fn ($j) => $j->ticketId === $t445->id);
        Queue::assertNotPushed(SendWhatsAppTicketImageJob::class, fn ($j) => $j->ticketId === $t447->id);
    }

    /* ---------- Required Test 3: Multiple holders in one booking ---------- */
    public function test_multiple_holders_only_dispatches_clicking_customer_tickets(): void
    {
        Queue::fake();

        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'Jonathan',
            'phone' => self::HOLDER,
            'tickets_count' => 3,
            'total_price' => 450,
            'status' => 'approved',
            'template_status' => 'delivered',
            'template_message_id' => 'wamid.TPL_MULTI',
            'reference_code' => 'SRC-MULTI',
        ]);

        $jonathanTicket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'Jonathan',
            'phone' => self::HOLDER,
            'ticket_code' => 'SRC-M-1',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        $holderATicket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'Holder A',
            'phone' => self::HOLDER_A,
            'ticket_code' => 'SRC-M-2',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        $holderBTicket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'Holder B',
            'phone' => self::HOLDER_B,
            'ticket_code' => 'SRC-M-3',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        // Jonathan clicks CTA
        $this->tap(self::HOLDER, 'wamid.TPL_MULTI', 'wamid.IN_JONATHAN')->assertOk();

        // Only Jonathan's ticket dispatched
        Queue::assertPushed(SendWhatsAppTicketImageJob::class, 1);
        Queue::assertPushed(SendWhatsAppTicketImageJob::class, fn ($j) => $j->ticketId === $jonathanTicket->id);
        Queue::assertNotPushed(SendWhatsAppTicketImageJob::class, fn ($j) => $j->ticketId === $holderATicket->id);
        Queue::assertNotPushed(SendWhatsAppTicketImageJob::class, fn ($j) => $j->ticketId === $holderBTicket->id);
    }

    /* ---------- Required Test 4: Multiple bookings + multiple holders ---------- */
    public function test_multiple_bookings_and_multiple_holders_only_dispatches_for_clicking_phone(): void
    {
        Queue::fake();

        // Booking 1: Jonathan + Holder A
        $b1 = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'Jonathan',
            'phone' => self::HOLDER,
            'tickets_count' => 2,
            'total_price' => 300,
            'status' => 'approved',
            'template_status' => 'delivered',
            'reference_code' => 'SRC-B1',
        ]);
        $t1Jonathan = Ticket::create([
            'booking_id' => $b1->id,
            'name' => 'Jonathan',
            'phone' => self::HOLDER,
            'ticket_code' => 'SRC-B1-T1',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);
        $t1HolderA = Ticket::create([
            'booking_id' => $b1->id,
            'name' => 'Holder A',
            'phone' => self::HOLDER_A,
            'ticket_code' => 'SRC-B1-T2',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        // Booking 2: Jonathan + Holder B
        $b2 = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'Jonathan',
            'phone' => self::HOLDER,
            'tickets_count' => 2,
            'total_price' => 300,
            'status' => 'approved',
            'template_status' => 'delivered',
            'template_message_id' => 'wamid.TPL_B2',
            'reference_code' => 'SRC-B2',
        ]);
        $t2Jonathan = Ticket::create([
            'booking_id' => $b2->id,
            'name' => 'Jonathan',
            'phone' => self::HOLDER,
            'ticket_code' => 'SRC-B2-T1',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);
        $t2HolderB = Ticket::create([
            'booking_id' => $b2->id,
            'name' => 'Holder B',
            'phone' => self::HOLDER_B,
            'ticket_code' => 'SRC-B2-T2',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        // Jonathan clicks CTA
        $this->tap(self::HOLDER, 'wamid.TPL_B2', 'wamid.IN_TAP_ALL')->assertOk();

        // Exactly Jonathan's 2 tickets dispatched, no other holders
        Queue::assertPushed(SendWhatsAppTicketImageJob::class, 2);
        Queue::assertPushed(SendWhatsAppTicketImageJob::class, fn ($j) => $j->ticketId === $t1Jonathan->id);
        Queue::assertPushed(SendWhatsAppTicketImageJob::class, fn ($j) => $j->ticketId === $t2Jonathan->id);
        Queue::assertNotPushed(SendWhatsAppTicketImageJob::class, fn ($j) => $j->ticketId === $t1HolderA->id);
        Queue::assertNotPushed(SendWhatsAppTicketImageJob::class, fn ($j) => $j->ticketId === $t2HolderB->id);
    }

    /* ---------- Required Test 5: Duplicate CTA ---------- */
    public function test_duplicate_cta_does_not_dispatch_duplicate_qr_sends(): void
    {
        Queue::fake();

        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'Jonathan',
            'phone' => self::HOLDER,
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'delivered',
            'template_message_id' => 'wamid.TPL_DUP',
            'reference_code' => 'SRC-DUP',
        ]);
        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'Jonathan',
            'phone' => self::HOLDER,
            'ticket_code' => 'SRC-DUP-T1',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        // Tap 1
        $this->tap(self::HOLDER, 'wamid.TPL_DUP', 'wamid.IN_TAP_1')->assertOk();
        Queue::assertPushed(SendWhatsAppTicketImageJob::class, 1);

        // Tap 2 (duplicate tap with different inbound message ID)
        $this->tap(self::HOLDER, 'wamid.TPL_DUP', 'wamid.IN_TAP_2')->assertOk();

        // Total pushes remains 1 (no duplicate job queued)
        Queue::assertPushed(SendWhatsAppTicketImageJob::class, 1);
    }

    /* ---------- Required Test 6: WAMID overwritten scenario ---------- */
    public function test_wamid_overwritten_scenario_resolves_customer_and_dispatches_all_eligible_tickets(): void
    {
        Queue::fake();

        // Booking 445 -> sent Template WAMID A
        $b445 = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'Jonathan',
            'phone' => self::HOLDER,
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'delivered',
            'template_message_id' => 'wamid.TPL_A',
            'reference_code' => 'SRC-445',
        ]);
        $t445 = Ticket::create([
            'booking_id' => $b445->id,
            'name' => 'Jonathan',
            'phone' => self::HOLDER,
            'ticket_code' => 'SRC-445-T1',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);
        WhatsAppTemplateMessage::record('wamid.TPL_A', $b445->id, self::HOLDER);

        // Booking 446 -> sent Template WAMID B
        $b446 = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'Jonathan',
            'phone' => self::HOLDER,
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'delivered',
            'template_message_id' => 'wamid.TPL_B',
            'reference_code' => 'SRC-446',
        ]);
        $t446 = Ticket::create([
            'booking_id' => $b446->id,
            'name' => 'Jonathan',
            'phone' => self::HOLDER,
            'ticket_code' => 'SRC-446-T1',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);
        WhatsAppTemplateMessage::record('wamid.TPL_B', $b446->id, self::HOLDER);

        // Booking 447 -> sent Template WAMID C (and overwrote booking.template_message_id)
        $b447 = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'Jonathan',
            'phone' => self::HOLDER,
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'delivered',
            'template_message_id' => 'wamid.TPL_C',
            'reference_code' => 'SRC-447',
        ]);
        $t447 = Ticket::create([
            'booking_id' => $b447->id,
            'name' => 'Jonathan',
            'phone' => self::HOLDER,
            'ticket_code' => 'SRC-447-T1',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);
        WhatsAppTemplateMessage::record('wamid.TPL_C', $b447->id, self::HOLDER);

        // In booking table, template_message_id ends up containing C:
        $b445->update(['template_message_id' => 'wamid.TPL_C']);
        $b446->update(['template_message_id' => 'wamid.TPL_C']);

        // Jonathan clicks CTA using WAMID A (an earlier template whose WAMID was overwritten in booking table)
        $this->tap(self::HOLDER, 'wamid.TPL_A', 'wamid.IN_TAP_OVERWRITTEN')
            ->assertOk()
            ->assertJsonMissing(['status' => 'ambiguous']);

        // Expected: All 3 tickets dispatched across all approved bookings without WHATSAPP_CTA_AMBIGUOUS_BOOKING
        Queue::assertPushed(SendWhatsAppTicketImageJob::class, 3);
        Queue::assertPushed(SendWhatsAppTicketImageJob::class, fn ($j) => $j->ticketId === $t445->id);
        Queue::assertPushed(SendWhatsAppTicketImageJob::class, fn ($j) => $j->ticketId === $t446->id);
        Queue::assertPushed(SendWhatsAppTicketImageJob::class, fn ($j) => $j->ticketId === $t447->id);
    }

    public function test_booker_tap_with_matching_context_still_only_receives_own_ticket(): void
    {
        Queue::fake();
        [$booking, $bookerTicket, $holderTicket] = $this->makeBooking445();
        $booking->update(['template_message_id' => 'wamid.TPL_445_BOOKER']);

        $this->tap(self::BOOKER, 'wamid.TPL_445_BOOKER', 'wamid.IN_445_BOOKER')->assertOk();

        Queue::assertPushed(SendWhatsAppTicketImageJob::class, 1);
        Queue::assertPushed(SendWhatsAppTicketImageJob::class, fn ($job) => $job->ticketId === $bookerTicket->id);
        $this->assertNull($holderTicket->fresh()->claimed_at);
    }

    /* =========================================================================
     * Focused Delivery-State Audit Tests (Scenarios 1 - 9)
     * ========================================================================= */

    /** Scenario 1: Delivered ticket is excluded (never resent). */
    public function test_delivered_ticket_is_excluded(): void
    {
        Queue::fake();
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'Jonathan',
            'phone' => self::HOLDER,
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'delivered',
            'template_message_id' => 'wamid.TPL_DELIVERED',
            'reference_code' => 'SRC-DELIVERED',
        ]);
        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'Jonathan',
            'phone' => self::HOLDER,
            'ticket_code' => 'SRC-DEL-1',
            'whatsapp_sent' => true,
            'delivery_status' => 'delivered',
            'last_delivered_at' => now()->subMinutes(10),
        ]);

        $this->tap(self::HOLDER, 'wamid.TPL_DELIVERED', 'wamid.IN_DEL')->assertOk();

        Queue::assertNothingPushed();
        $this->assertSame('delivered', $ticket->fresh()->delivery_status);
        $this->assertTrue((bool) $ticket->fresh()->whatsapp_sent);
    }

    /** Scenario 2: Read ticket is excluded (never resent). */
    public function test_read_ticket_is_excluded(): void
    {
        Queue::fake();
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'Jonathan',
            'phone' => self::HOLDER,
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'delivered',
            'template_message_id' => 'wamid.TPL_READ',
            'reference_code' => 'SRC-READ',
        ]);
        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'Jonathan',
            'phone' => self::HOLDER,
            'ticket_code' => 'SRC-READ-1',
            'whatsapp_sent' => true,
            'delivery_status' => 'read',
            'last_delivered_at' => now()->subMinutes(10),
        ]);

        $this->tap(self::HOLDER, 'wamid.TPL_READ', 'wamid.IN_READ')->assertOk();

        Queue::assertNothingPushed();
        $this->assertSame('read', $ticket->fresh()->delivery_status);
        $this->assertTrue((bool) $ticket->fresh()->whatsapp_sent);
    }

    /** Scenario 3: Sent-but-not-delivered ticket follows the intended retry/state-machine behavior. */
    public function test_sent_but_not_delivered_ticket_follows_retry_policy(): void
    {
        Queue::fake();
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'Jonathan',
            'phone' => self::HOLDER,
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'delivered',
            'template_message_id' => 'wamid.TPL_SENT',
            'reference_code' => 'SRC-SENT',
        ]);

        // Ticket was accepted by Meta (sent) 5 minutes ago, but never confirmed delivered
        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'Jonathan',
            'phone' => self::HOLDER,
            'ticket_code' => 'SRC-SENT-1',
            'whatsapp_sent' => true,
            'delivery_status' => 'sent',
            'claimed_at' => now()->subMinutes(5),
            'whatsapp_message_id' => 'wamid.IMG_UNCONFIRMED',
        ]);

        $this->tap(self::HOLDER, 'wamid.TPL_SENT', 'wamid.IN_SENT_RETRY')->assertOk();

        // Customer retry should queue the job and reset status to pending for worker claim
        Queue::assertPushed(SendWhatsAppTicketImageJob::class, 1);
        Queue::assertPushed(SendWhatsAppTicketImageJob::class, fn ($j) => $j->ticketId === $ticket->id);

        $fresh = $ticket->fresh();
        $this->assertSame('pending', $fresh->delivery_status);
        $this->assertFalse((bool) $fresh->whatsapp_sent);

        // However, if sent immediately within in-flight window (e.g., 5 seconds ago), immediate duplicate tap is ignored
        Queue::fake();
        $ticket->update(['delivery_status' => 'sent', 'claimed_at' => now()->subSeconds(5)]);
        $this->tap(self::HOLDER, 'wamid.TPL_SENT', 'wamid.IN_SENT_IMMEDIATE')->assertOk();
        Queue::assertNothingPushed();
    }

    /** Scenario 4: Failed ticket can be retried. */
    public function test_failed_ticket_can_be_retried(): void
    {
        Queue::fake();
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'Jonathan',
            'phone' => self::HOLDER,
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'delivered',
            'template_message_id' => 'wamid.TPL_FAILED',
            'reference_code' => 'SRC-FAILED',
        ]);
        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'Jonathan',
            'phone' => self::HOLDER,
            'ticket_code' => 'SRC-FAIL-1',
            'whatsapp_sent' => false,
            'delivery_status' => 'failed',
            'delivery_error' => 'Connection timed out',
        ]);

        $this->tap(self::HOLDER, 'wamid.TPL_FAILED', 'wamid.IN_FAIL_RETRY')->assertOk();

        Queue::assertPushed(SendWhatsAppTicketImageJob::class, 1);
        Queue::assertPushed(SendWhatsAppTicketImageJob::class, fn ($j) => $j->ticketId === $ticket->id);

        $fresh = $ticket->fresh();
        $this->assertSame('pending', $fresh->delivery_status);
        $this->assertNull($fresh->delivery_error);
    }

    /** Scenario 5: Pending ticket can be sent. */
    public function test_pending_ticket_can_be_sent(): void
    {
        Queue::fake();
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'Jonathan',
            'phone' => self::HOLDER,
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'delivered',
            'template_message_id' => 'wamid.TPL_PENDING',
            'reference_code' => 'SRC-PENDING',
        ]);
        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'Jonathan',
            'phone' => self::HOLDER,
            'ticket_code' => 'SRC-PEND-1',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
            'claimed_at' => null,
        ]);

        $this->tap(self::HOLDER, 'wamid.TPL_PENDING', 'wamid.IN_PEND')->assertOk();

        Queue::assertPushed(SendWhatsAppTicketImageJob::class, 1);
        Queue::assertPushed(SendWhatsAppTicketImageJob::class, fn ($j) => $j->ticketId === $ticket->id);
        $this->assertNotNull($ticket->fresh()->claimed_at);
    }

    /** Scenario 6: Sending ticket does not create a duplicate job. */
    public function test_sending_ticket_does_not_create_duplicate_job(): void
    {
        Queue::fake();
        $booking = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'Jonathan',
            'phone' => self::HOLDER,
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'delivered',
            'template_message_id' => 'wamid.TPL_SENDING',
            'reference_code' => 'SRC-SENDING',
        ]);
        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'name' => 'Jonathan',
            'phone' => self::HOLDER,
            'ticket_code' => 'SRC-SND-1',
            'whatsapp_sent' => false,
            'delivery_status' => 'sending',
            'claimed_at' => now()->subSeconds(2),
        ]);

        $this->tap(self::HOLDER, 'wamid.TPL_SENDING', 'wamid.IN_SENDING')->assertOk();

        Queue::assertNothingPushed();
        $this->assertSame('sending', $ticket->fresh()->delivery_status);
    }

    /** Scenario 7: Customer with multiple bookings receives all eligible tickets. */
    public function test_customer_with_multiple_bookings_receives_all_eligible_tickets(): void
    {
        Queue::fake();
        $b1 = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'Jonathan',
            'phone' => self::HOLDER,
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'delivered',
            'template_message_id' => 'wamid.TPL_MB_1',
            'reference_code' => 'SRC-MB1',
        ]);
        $t1 = Ticket::create([
            'booking_id' => $b1->id,
            'name' => 'Jonathan',
            'phone' => self::HOLDER,
            'ticket_code' => 'SRC-MB1-T1',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        $b2 = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'Jonathan',
            'phone' => self::HOLDER,
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'delivered',
            'template_message_id' => 'wamid.TPL_MB_2',
            'reference_code' => 'SRC-MB2',
        ]);
        $t2 = Ticket::create([
            'booking_id' => $b2->id,
            'name' => 'Jonathan',
            'phone' => self::HOLDER,
            'ticket_code' => 'SRC-MB2-T1',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        $this->tap(self::HOLDER, 'wamid.TPL_MB_2', 'wamid.IN_MB')->assertOk();

        Queue::assertPushed(SendWhatsAppTicketImageJob::class, 2);
        Queue::assertPushed(SendWhatsAppTicketImageJob::class, fn ($j) => $j->ticketId === $t1->id);
        Queue::assertPushed(SendWhatsAppTicketImageJob::class, fn ($j) => $j->ticketId === $t2->id);
    }

    /** Scenario 8: Customer with multiple holders receives only own tickets. */
    public function test_customer_with_multiple_holders_receives_only_own_tickets(): void
    {
        Queue::fake();
        $b = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'Jonathan',
            'phone' => self::HOLDER,
            'tickets_count' => 2,
            'total_price' => 300,
            'status' => 'approved',
            'template_status' => 'delivered',
            'template_message_id' => 'wamid.TPL_MH',
            'reference_code' => 'SRC-MH',
        ]);
        $jonathanTicket = Ticket::create([
            'booking_id' => $b->id,
            'name' => 'Jonathan',
            'phone' => self::HOLDER,
            'ticket_code' => 'SRC-MH-T1',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);
        $otherTicket = Ticket::create([
            'booking_id' => $b->id,
            'name' => 'Friend',
            'phone' => self::HOLDER_A,
            'ticket_code' => 'SRC-MH-T2',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        $this->tap(self::HOLDER, 'wamid.TPL_MH', 'wamid.IN_MH_TAP')->assertOk();

        Queue::assertPushed(SendWhatsAppTicketImageJob::class, 1);
        Queue::assertPushed(SendWhatsAppTicketImageJob::class, fn ($j) => $j->ticketId === $jonathanTicket->id);
        Queue::assertNotPushed(SendWhatsAppTicketImageJob::class, fn ($j) => $j->ticketId === $otherTicket->id);
    }

    /** Scenario 9: Duplicate CTA does not duplicate already-processing/delivered tickets. */
    public function test_duplicate_cta_does_not_duplicate_processing_or_delivered_tickets(): void
    {
        Queue::fake();
        $b = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'Jonathan',
            'phone' => self::HOLDER,
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'delivered',
            'template_message_id' => 'wamid.TPL_D9',
            'reference_code' => 'SRC-D9',
        ]);
        $t = Ticket::create([
            'booking_id' => $b->id,
            'name' => 'Jonathan',
            'phone' => self::HOLDER,
            'ticket_code' => 'SRC-D9-T1',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        // First tap -> dispatched
        $this->tap(self::HOLDER, 'wamid.TPL_D9', 'wamid.IN_D9_1')->assertOk();
        Queue::assertPushed(SendWhatsAppTicketImageJob::class, 1);

        // Second tap immediately while processing -> excluded
        $this->tap(self::HOLDER, 'wamid.TPL_D9', 'wamid.IN_D9_2')->assertOk();
        Queue::assertPushed(SendWhatsAppTicketImageJob::class, 1); // still only 1 total

        // Now simulate delivery confirmation from Meta
        $t->update(['delivery_status' => 'delivered', 'whatsapp_sent' => true]);

        // Third tap after delivered -> excluded
        $this->tap(self::HOLDER, 'wamid.TPL_D9', 'wamid.IN_D9_3')->assertOk();
        Queue::assertPushed(SendWhatsAppTicketImageJob::class, 1); // still only 1 total
    }
}
