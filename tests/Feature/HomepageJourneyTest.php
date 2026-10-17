<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Play;
use App\Models\ShowTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageJourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_approved_intro_and_direct_show_reveal_without_post_intro_bloat(): void
    {
        $play = Play::create([
            'title' => 'مسرحية الصرخة الكبرى',
            'description' => 'عرض مسرحي يناقش قضايا الإنسان والوعي على خشبة مسرح الصرخة.',
            'poster' => 'posters/elsar5a-main.jpg',
            'price' => 120,
            'is_active' => true,
        ]);

        ShowTime::create([
            'play_id' => $play->id,
            'starts_at' => now()->addDays(4)->setTime(19, 30),
            'capacity' => 100,
            'price' => 150,
            'status' => 'open',
        ]);

        $response = $this->get(route('home'));
        $response->assertStatus(200);

        // 1. Approved Intro is preserved (Act I)
        $response->assertSee('id="theatre-journey"', false);
        $response->assertSee('id="stage-intro-scene"', false);
        $response->assertSee('id="curtain-layer"', false);
        $response->assertSee('فريق الصرخة المسرحي');
        $response->assertSee('نجول نصرخ فيزداد العقل وعيا');

        // 2. Obsolete post-intro filler scenes are completely removed
        $response->assertDontSee('id="stage-identity-scene"', false);
        $response->assertDontSee('id="stage-corridor-scene"', false);
        $response->assertDontSee('id="continuation-world"', false);

        // 3. Real current production Playbill Reveal is rendered with canonical booking route (Act II)
        $response->assertSee('id="stage-show-scene"', false);
        $response->assertSee('id="playbill-showcase"', false);
        $response->assertSee('مسرحية الصرخة الكبرى');
        $response->assertSee('150');
        $response->assertSee('متاح للحجز الآن');
        $response->assertSee(route('shows.book', $play), false);
        $response->assertSee(asset('storage/posters/elsar5a-main.jpg'), false);
    }

    public function test_homepage_displays_active_play_even_when_all_showtimes_are_in_the_past(): void
    {
        $play = Play::create([
            'title' => 'ليلة المحاكمة',
            'description' => 'دراما مسرحية حية من إنتاج فريق الصرخة.',
            'poster' => 'https://res.cloudinary.com/demo/image/upload/v1/sample.jpg',
            'price' => 100,
            'is_active' => true,
        ]);

        ShowTime::create([
            'play_id' => $play->id,
            'starts_at' => now()->subDays(2)->setTime(20, 0),
            'capacity' => 80,
            'price' => 100,
            'status' => 'open',
        ]);

        $response = $this->get(route('home'));
        $response->assertStatus(200);

        $response->assertSee('ليلة المحاكمة');
        $response->assertDontSee('ترقبوا العرض المسرحي القادم قريباً');
        $response->assertSee('https://res.cloudinary.com/demo/image/upload/v1/sample.jpg', false);
        $response->assertSee(route('shows.book', $play), false);
    }

    public function test_homepage_prioritizes_active_play_with_upcoming_bookable_showtime_over_expired_play(): void
    {
        $expiredPlay = Play::create([
            'title' => 'عرض الأسبوع الماضي',
            'description' => 'عرض انتهت مواعيده.',
            'price' => 90,
            'is_active' => true,
        ]);

        ShowTime::create([
            'play_id' => $expiredPlay->id,
            'starts_at' => now()->subDays(5),
            'capacity' => 100,
            'price' => 90,
            'status' => 'open',
        ]);

        $upcomingPlay = Play::create([
            'title' => 'عرض الموسم الجديد',
            'description' => 'العرض القادم المتاح للحجز.',
            'price' => 140,
            'is_active' => true,
        ]);

        ShowTime::create([
            'play_id' => $upcomingPlay->id,
            'starts_at' => now()->addDays(2),
            'capacity' => 100,
            'price' => 140,
            'status' => 'open',
        ]);

        $response = $this->get(route('home'));
        $response->assertStatus(200);

        $response->assertSee('عرض الموسم الجديد');
        $response->assertSee(route('shows.book', $upcomingPlay), false);
    }

    public function test_homepage_shows_clean_fallback_only_when_no_active_plays_exist(): void
    {
        Play::create([
            'title' => 'عرض أرشيفي قديم',
            'description' => 'أرشيف',
            'price' => 80,
            'is_active' => false,
        ]);

        $response = $this->get(route('home'));
        $response->assertStatus(200);

        $response->assertSee('ترقبوا العرض المسرحي القادم قريباً');
        $response->assertDontSee('عرض أرشيفي قديم');
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

        // Leave 6 seats remaining (1–10 seats -> critical exact count)
        Booking::create([
            'reference' => 'BK-SCARCITY-6',
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
        $response->assertSee('playbill-badge--critical', false);
    }
}
