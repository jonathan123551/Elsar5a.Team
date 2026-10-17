<?php

namespace Tests\Feature;

use App\Models\Show;
use App\Models\ShowTime;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_booking_page_renders_for_available_show_time(): void
    {
        $show = Show::create([
            'title' => 'Smoke Show',
            'description' => 'Smoke test show',
            'is_active' => true,
        ]);

        $showTime = ShowTime::create([
            'show_id' => $show->id,
            'date' => '2026-06-01',
            'time' => '19:00:00',
            'ticket_price' => 100,
            'total_tickets' => 10,
            'available_tickets' => 10,
            'is_sold_out' => false,
        ]);

        $this->get(route('bookings.create', $showTime))
            ->assertOk()
            ->assertViewIs('bookings.create')
            ->assertSee('Smoke Show')
            ->assertSee('01-06-2026')
            ->assertSee('100 جنيه')
            ->assertSee(route('bookings.store', $showTime), false);
    }

    public function test_booking_confirmation_shows_real_reference_amount_and_pending_state(): void
    {
        $show = Show::create([
            'title' => 'Smoke Show',
            'description' => 'Smoke test show',
            'is_active' => true,
        ]);

        $showTime = ShowTime::create([
            'show_id' => $show->id,
            'date' => '2026-06-01',
            'time' => '19:00:00',
            'ticket_price' => 100,
            'total_tickets' => 10,
            'available_tickets' => 10,
            'is_sold_out' => false,
        ]);

        $booking = Booking::create([
            'show_time_id' => $showTime->id,
            'full_name' => 'Test Customer',
            'phone' => '201012345678',
            'tickets_count' => 2,
            'total_price' => 200,
            'status' => 'pending',
            'reference_code' => 'SRC-20260601-ABC123',
        ]);

        $this->get(route('bookings.thankyou', $booking->reference_code))
            ->assertOk()
            ->assertSee('SRC-20260601-ABC123')
            ->assertSee('طلبك قيد المراجعة')
            ->assertSee('Smoke Show')
            ->assertSee('••••••5678')
            ->assertDontSee('201012345678')
            ->assertSee('لم يصبح الحجز مؤكدًا بعد');
    }
}
