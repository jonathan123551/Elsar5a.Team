<?php

namespace Tests\Feature;

use App\Models\Show;
use App\Models\ShowTime;
use App\Models\Booking;
use App\Models\Setting;
use Cloudinary\Api\Upload\UploadApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class BookingRedesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_page_renders_four_stages_and_multiple_showtimes(): void
    {
        Setting::set('transfer_wallet', '01012345678');
        Setting::set('transfer_insta', 'elsar5a@instapay');

        $show = Show::create([
            'title' => 'ملحمة السراب',
            'description' => 'عرض مسرحي درامي يعالج صراع الإنسان مع أوهامه ورغباته.',
            'is_active' => true,
        ]);

        $st1 = ShowTime::create([
            'show_id' => $show->id,
            'date' => '2026-11-20',
            'time' => '19:00:00',
            'ticket_price' => 150,
            'total_tickets' => 50,
            'available_tickets' => 50,
            'is_sold_out' => false,
        ]);

        $st2 = ShowTime::create([
            'show_id' => $show->id,
            'date' => '2026-11-21',
            'time' => '20:30:00',
            'ticket_price' => 180,
            'total_tickets' => 50,
            'available_tickets' => 50,
            'is_sold_out' => false,
        ]);

        $response = $this->get(route('bookings.create', $st1));
        $response->assertOk();
        $response->assertViewIs('bookings.create');

        // Check for 4 stages
        $response->assertSee('id="stage-01"', false);
        $response->assertSee('id="stage-02"', false);
        $response->assertSee('id="stage-03"', false);
        $response->assertSee('id="stage-04"', false);

        // Check for multiple showtimes switcher
        $response->assertSee(route('bookings.create', $st2), false);

        // Check for payment methods
        $response->assertSee('01012345678');
        $response->assertSee('elsar5a@instapay');

        // Check for ticket prices
        $response->assertSee('150');
    }

    public function test_booking_validation_errors_for_missing_fields(): void
    {
        $show = Show::create([
            'title' => 'ملحمة السراب',
            'is_active' => true,
        ]);

        $showTime = ShowTime::create([
            'show_id' => $show->id,
            'date' => '2026-11-20',
            'time' => '19:00:00',
            'ticket_price' => 100,
            'total_tickets' => 20,
            'available_tickets' => 20,
            'is_sold_out' => false,
        ]);

        $response = $this->post(route('bookings.store', $showTime), []);
        $response->assertSessionHasErrors(['names', 'phones', 'payment_screenshot']);
    }

    public function test_successful_booking_creates_tickets_and_redirects_to_thankyou(): void
    {
        $show = Show::create([
            'title' => 'ملحمة السراب',
            'is_active' => true,
        ]);

        $showTime = ShowTime::create([
            'show_id' => $show->id,
            'date' => '2026-11-20',
            'time' => '19:00:00',
            'ticket_price' => 100,
            'total_tickets' => 20,
            'available_tickets' => 20,
            'is_sold_out' => false,
        ]);

        $mockUploadApi = Mockery::mock('overload:' . UploadApi::class);
        $mockUploadApi->shouldReceive('upload')
            ->once()
            ->andReturn([
                'secure_url' => 'https://res.cloudinary.com/demo/image/upload/sample_receipt.jpg',
                'public_id'  => 'payments/screenshots/sample_receipt',
            ]);

        $screenshot = UploadedFile::fake()->image('receipt.jpg', 600, 800);

        $response = $this->post(route('bookings.store', $showTime), [
            'names' => ['مينا عاطف', 'يوحنا وجيه'],
            'phones' => ['01012345678', '01298765432'],
            'payment_screenshot' => $screenshot,
            'idempotency_token' => 'idem_' . Str::random(16),
        ]);

        $response->assertRedirect();
        $booking = Booking::where('show_time_id', $showTime->id)->first();
        $this->assertNotNull($booking);
        $this->assertSame(2, $booking->tickets_count);
        $this->assertSame(200, (int) $booking->total_price);
        $this->assertSame('مينا عاطف', $booking->full_name);
        $this->assertCount(2, $booking->tickets);

        $response->assertRedirect(route('bookings.thankyou', ['reference' => $booking->reference_code]));

        // Check inventory decremented from 20 to 18
        $showTime->refresh();
        $this->assertSame(18, (int) $showTime->available_tickets);

        // Check thank-you page renders
        $thankYouResponse = $this->get(route('bookings.thankyou', ['reference' => $booking->reference_code]));
        $thankYouResponse->assertOk();
        $thankYouResponse->assertSee($booking->reference_code);
    }
}
