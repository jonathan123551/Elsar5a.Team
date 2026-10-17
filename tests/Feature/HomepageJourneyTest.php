<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Play;
use App\Models\Show;
use App\Models\ShowTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageJourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_approved_intro_and_removes_manifesto_bloat(): void
    {
        $play = Play::create([
            'title' => 'الصرخة',
            'description' => 'عرض مسرحي يطرح أسئلة جريئة على خشبة المسرح.',
            'poster' => 'posters/elsar5a.jpg',
            'price' => 120,
            'is_active' => true,
        ]);

        ShowTime::create([
            'play_id' => $play->id,
            'starts_at' => now()->addDays(4)->setTime(20, 0),
            'capacity' => 80,
            'price' => 120,
            'status' => 'open',
        ]);

        $response = $this->get(route('home'));

        $response->assertStatus(200);

        // Approved Intro elements must remain intact
        $response->assertSee('id="curtain-left"', false);
        $response->assertSee('id="curtain-right"', false);
        $response->assertSee('id="curtain-seam"', false);
        $response->assertSee('id="stage-logo-scene"', false);
        $response->assertSee('فريق الصرخة المسرحي');
        $response->assertSee('❤ نجول نصرخ فيزداد العقل وعيا ❤');

        // Removed filler scenes and manifesto/archive bloat must NOT appear on homepage
        $response->assertDontSee('id="stage-identity-scene"', false);
        $response->assertDontSee('id="stage-corridor-scene"', false);
        $response->assertDontSee('id="continuation-world"', false);
        $response->assertDontSee('كثيرًا ما فسدت عقولنا');
        $response->assertDontSee('أعمالنا وعروضنا المسرحية');
    }

    public function test_homepage_connects_real_upcoming_production_and_booking_cta(): void
    {
        $play = Play::create([
            'title' => 'ليلة المحاكمة',
            'description' => 'دراما مسرحية تفاعلية من إنتاج فريق الصرخة.',
            'poster' => 'posters/trial-night.webp',
            'price' => 150,
            'is_active' => true,
        ]);

        ShowTime::create([
            'play_id' => $play->id,
            'starts_at' => now()->addDays(2)->setTime(21, 0),
            'capacity' => 60,
            'price' => 150,
            'status' => 'open',
        ]);

        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('ليلة المحاكمة');
        $response->assertSee('دراما مسرحية تفاعلية من إنتاج فريق الصرخة.');
        $response->assertSee('150 ج.م');
        $response->assertSee('متاح للحجز الآن');
        $response->assertSee(route('shows.book', $play), false);
        $response->assertSee('storage/posters/trial-night.webp', false);
    }

    public function test_homepage_still_displays_active_play_when_showtimes_are_in_the_past(): void
    {
        $play = Play::create([
            'title' => 'ما وراء الستار',
            'description' => 'العرض المسرحي الرئيسي للفريق هذا الموسم.',
            'poster' => 'https://res.cloudinary.com/demo/image/upload/sample.jpg',
            'price' => 100,
            'is_active' => true,
        ]);

        ShowTime::create([
            'play_id' => $play->id,
            'starts_at' => now()->subDays(2)->setTime(19, 0),
            'capacity' => 50,
            'price' => 100,
            'status' => 'open',
        ]);

        $response = $this->get(route('home'));

        $response->assertStatus(200);
        // Must surface the real active play rather than dropping to the generic empty state
        $response->assertSee('ما وراء الستار');
        $response->assertSee('https://res.cloudinary.com/demo/image/upload/sample.jpg', false);
        $response->assertSee(route('shows.book', $play), false);
        $response->assertDontSee('ترقبوا العرض المسرحي القادم قريباً');
    }

    public function test_homepage_ignores_past_showtime_and_picks_next_upcoming_bookable_showtime(): void
    {
        $play = Play::create([
            'title' => 'صوت الخشبة',
            'description' => 'عرض حي.',
            'poster' => 'images/stage-voice.png',
            'price' => 90,
            'is_active' => true,
        ]);

        // Past showtime with different price
        ShowTime::create([
            'play_id' => $play->id,
            'starts_at' => now()->subDays(5)->setTime(18, 0),
            'capacity' => 50,
            'price' => 70,
            'status' => 'open',
        ]);

        // Upcoming bookable showtime
        ShowTime::create([
            'play_id' => $play->id,
            'starts_at' => now()->addDays(3)->setTime(21, 0),
            'capacity' => 50,
            'price' => 135,
            'status' => 'open',
        ]);

        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('صوت الخشبة');
        $response->assertSee('135 ج.م');
        $response->assertSee('متاح للحجز الآن');
        $response->assertSee(asset('images/stage-voice.png'), false);
    }

    public function test_homepage_communicates_progressive_scarcity_accurately(): void
    {
        $play = Play::create([
            'title' => 'النداء الأخير',
            'description' => 'عرض محدود المقاعد.',
            'price' => 100,
            'is_active' => true,
        ]);

        $showtime = ShowTime::create([
            'play_id' => $play->id,
            'starts_at' => now()->addDays(2)->setTime(20, 0),
            'capacity' => 20,
            'price' => 100,
            'status' => 'open',
        ]);

        // Leave 6 seats remaining (high scarcity)
        Booking::create([
            'reference' => 'BK-SCARCITY-1',
            'play_id' => $play->id,
            'show_time_id' => $showtime->id,
            'customer_name' => 'أحمد محمد',
            'customer_phone' => '01000000000',
            'seats_count' => 14,
            'total_amount' => 1400,
            'payment_method' => 'instapay',
            'status' => Booking::STATUS_CONFIRMED,
        ]);

        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('متبقي 6 تذاكر فقط');
    }
}
