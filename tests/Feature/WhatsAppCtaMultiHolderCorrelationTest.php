<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppTicketImageJob;
use App\Models\Booking;
use App\Models\Show;
use App\Models\ShowTime;
use App\Models\Ticket;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Regression: booking #445 — a secondary ticket holder tapped "استلام التذكرة"
 * on the template whose WAMID was stored on booking.template_message_id, but the
 * webhook compared the tapper only against the BOOKER's phone and rejected it
 * with WHATSAPP_CTA_CONTEXT_PHONE_MISMATCH, so the QR was never sent.
 */
class WhatsAppCtaMultiHolderCorrelationTest extends TestCase
{
    use RefreshDatabase;

    private ShowTime $showTime;

    private const BOOKER = '201274663229';   // مريم — booker + ticket 1
    private const HOLDER = '201222356357';   // Jonathan — ticket 2
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
            // Last accepted template WAMID — the one sent to the secondary holder.
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

    /* ---------- 1–3: phone canonicalization ---------- */

    public function test_egyptian_phone_formats_resolve_to_same_whatsapp_identity(): void
    {
        foreach (['01222356357', '1222356357', '201222356357', '+201222356357', '00201222356357', '+20 122 235 6357'] as $input) {
            $this->assertSame('201222356357', PhoneNumber::canonical($input), $input);
        }
        $this->assertTrue(PhoneNumber::same('01222356357', '+201222356357'));
        $this->assertFalse(PhoneNumber::same('01222356357', '01274663229'));
        $this->assertFalse(PhoneNumber::same('', ''));
    }

    /* ---------- 4, 5, 8: production failure path ---------- */

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
        // The booker's ticket is NOT claimed/sent to the secondary holder.
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

        // 9. Existing WAMID status correlation still advances the ticket.
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

    /* ---------- 6: stranger with a valid context WAMID ---------- */

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

    public function test_cta_never_selects_ticket_from_another_booking(): void
    {
        Queue::fake();
        [, , $holderTicket] = $this->makeBooking445();

        // Another approved booking where the same holder also has a ticket.
        $other = Booking::create([
            'show_time_id' => $this->showTime->id,
            'full_name' => 'Other',
            'phone' => '201011111111',
            'tickets_count' => 1,
            'total_price' => 150,
            'status' => 'approved',
            'template_status' => 'delivered',
            'template_message_id' => 'wamid.TPL_OTHER',
            'reference_code' => 'SRC-OTHER',
        ]);
        $otherTicket = Ticket::create([
            'booking_id' => $other->id,
            'name' => 'Jonathan Maged',
            'phone' => self::HOLDER,
            'ticket_code' => 'SRC-OTHER-T1',
            'qr_image_path' => 'https://res.cloudinary.com/demo/image/upload/v1/qr_o.jpg',
            'whatsapp_sent' => false,
            'delivery_status' => 'pending',
        ]);

        $this->tap(self::HOLDER, self::TPL_HOLDER, 'wamid.IN_445_SCOPE')->assertOk();

        Queue::assertPushed(SendWhatsAppTicketImageJob::class, 1);
        Queue::assertPushed(SendWhatsAppTicketImageJob::class, fn ($job) => $job->ticketId === $holderTicket->id);
        Queue::assertNotPushed(SendWhatsAppTicketImageJob::class, fn ($job) => $job->ticketId === $otherTicket->id);
        $this->assertNull($otherTicket->fresh()->claimed_at);
    }

    /* ---------- 7: ambiguous phone fallback (no context) ---------- */

    public function test_ambiguous_phone_fallback_without_context_sends_nothing(): void
    {
        Queue::fake();
        [, , $t1] = $this->makeBooking445(self::HOLDER, 'SRC-A');
        [, , $t2] = $this->makeBooking445(self::HOLDER, 'SRC-B');

        $this->tap(self::HOLDER, null, 'wamid.IN_AMBIG')
            ->assertOk()
            ->assertJson(['status' => 'ambiguous']);

        Queue::assertNothingPushed();
        $this->assertNull($t1->fresh()->claimed_at);
        $this->assertNull($t2->fresh()->claimed_at);
    }

    /* ---------- booker path unchanged ---------- */

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
}
