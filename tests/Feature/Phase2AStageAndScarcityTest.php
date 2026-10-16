<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Show;
use App\Models\ShowTime;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2AStageAndScarcityTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_curtain_overlay_and_stage_architecture(): void
    {
        $show = Show::create([
            'title' => 'عرض الصرخة التجريبي',
            'description' => 'وصف العرض المسرحي التجريبي',
            'is_active' => true,
        ]);

        ShowTime::create([
            'show_id' => $show->id,
            'date' => Carbon::tomorrow()->toDateString(),
            'time' => '19:00:00',
            'ticket_price' => 150,
            'total_tickets' => 100,
            'available_tickets' => 80,
            'is_sold_out' => false,
        ]);

        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('id="cinema-curtain"', false);
        $response->assertSee('id="cinema-skip"', false);
        $response->assertSee('تخطّي الافتتاحية', false);
        $response->assertSee('Alexandria:wght@700;800;900', false);
        $response->assertSee('Readex+Pro:wght@400;500;600;700', false);
        $response->assertSee('css/elsar5a-stage.css', false);
        $response->assertSee('js/elsar5a-stage.js', false);
        $response->assertSee('class="camera-track"', false);
        $response->assertSee('class="camera-world"', false);
        $response->assertSee('class="camera-plane camera-auditorium"', false);
        $response->assertSee('class="camera-plane camera-aisle"', false);
        $response->assertSee('class="camera-plane camera-spotlight"', false);
        $response->assertSee('class="camera-plane camera-proscenium"', false);
        $response->assertSee('images/cinematic/curtain/closed-velvet.webp', false);
        $response->assertSee('images/cinematic/theatre/empty-stage.webp', false);
        $response->assertSee('فريق الصرخة المسرحي', false);
        $response->assertDontSee('فرقة الصرخة المسرحية', false);
        $response->assertSee('class="sr-only">فريق الصرخة المسرحي</h1>', false);
        $response->assertDontSee('<h1 id="team-title" data-reveal="title">', false);
        $response->assertSee('فنحن لا نريد سوى حواسكم.', false);
        $response->assertSee('class="opening-motto"', false);
        $response->assertSee('نجول نصرخ فيزداد العقل وعياً', false);
        $response->assertDontSee('❤ نجول نصرخ فيزداد العقل وعياً ❤', false);
        $response->assertSee('❤ نجول نصرخ فيزداد العقل وعيا ❤', false);
        $response->assertSee('كثيرًا ما فسدت عقولنا من ما حملته لها مدخلاتنا, ذلك هو ما نريد تغيره, فقط بالصراخ, الصراخ هنا و هناك.', false);
        $response->assertSee('ثم نصرخ نبحث فى مدخلاتكم لنخرج ما هو فاسد و نزرع بدلا منه ثمرا صالحنا, لا نريد سو عقولكم.', false);
        $response->assertSee('والان نصرخ بالتعاليم الصاحه لنغير ما فسد .', false);
        $response->assertSee('و فقط ما نحتاجه من الجميع هو المجيئ الى مصدر الصراخ, فدائما يكون على المسرح.', false);
        $response->assertSee('و قد كان سيدنا و معلمنا و الهنا وهو على الارض يجول يصنع خيرا و نحن على خطاه.', false);
    }

    public function test_shows_index_route_does_not_render_curtain_overlay(): void
    {
        $show = Show::create([
            'title' => 'عرض الصرخة الثاني',
            'description' => 'وصف العرض المسرحي',
            'is_active' => true,
        ]);

        $response = $this->get('/shows');

        $response->assertOk();
        $response->assertDontSee('id="cinema-curtain"', false);
        $response->assertDontSee('id="cinema-skip"', false);
    }

    public function test_obvious_test_shows_are_not_exposed_as_homepage_productions(): void
    {
        $show = Show::create([
            'title' => 'Test',
            'description' => 'Fhszh',
            'poster_path' => 'https://res.cloudinary.com/example/image/upload/test-assessment.jpg',
            'is_active' => true,
        ]);

        ShowTime::create([
            'show_id' => $show->id,
            'date' => Carbon::tomorrow()->toDateString(),
            'time' => '19:00:00',
            'ticket_price' => 100,
            'total_tickets' => 50,
            'available_tickets' => 50,
            'is_sold_out' => false,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('Fhszh', false);
        $response->assertDontSee('test-assessment.jpg', false);
        $response->assertSee('خشبة المسرح تستعد للعرض القادم', false);
    }

    public function test_public_shows_listing_and_detail_do_not_advertise_test_placeholders(): void
    {
        $show = Show::create([
            'title' => 'Test',
            'description' => 'Fhszh',
            'poster_path' => 'https://res.cloudinary.com/example/image/upload/test-assessment.jpg',
            'is_active' => true,
        ]);
        ShowTime::create([
            'show_id' => $show->id,
            'date' => Carbon::tomorrow()->toDateString(),
            'time' => '19:00:00',
            'ticket_price' => 100,
            'total_tickets' => 50,
            'available_tickets' => 50,
            'is_sold_out' => false,
        ]);

        $this->get('/shows')
            ->assertOk()
            ->assertDontSee('Fhszh', false)
            ->assertDontSee('test-assessment.jpg', false)
            ->assertSee('لا توجد عروض متاحة في الوقت الحالي.', false);

        $this->get(route('shows.show', $show))->assertNotFound();
    }

    public function test_inactive_and_test_productions_are_not_featured(): void
    {
        foreach ([['Test', true], ['عرض غير نشط', false]] as [$title, $active]) {
            $show = Show::create([
                'title' => $title,
                'is_active' => $active,
            ]);
            ShowTime::create([
                'show_id' => $show->id,
                'date' => Carbon::tomorrow()->toDateString(),
                'time' => '19:00:00',
                'ticket_price' => 100,
                'total_tickets' => 20,
                'available_tickets' => 20,
                'is_sold_out' => false,
            ]);
        }

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('id="featured-title"', false);
        $response->assertSee('خشبة المسرح تستعد للعرض القادم', false);
        $response->assertDontSee('عرض غير نشط', false);
    }

    public function test_reduced_motion_uses_static_content_and_releases_hidden_playbill(): void
    {
        $css = file_get_contents(public_path('css/elsar5a-stage.css'));
        $script = file_get_contents(public_path('js/elsar5a-stage.js'));

        $this->assertStringContainsString('prefers-reduced-motion: reduce', $css);
        $this->assertStringContainsString('.no-js .camera-track { height:auto; }', $css);
        $this->assertStringContainsString("window.matchMedia('(prefers-reduced-motion: reduce)')", $script);
        $this->assertStringContainsString("panel.removeAttribute('inert')", $script);
        $this->assertStringContainsString("panel.style.setProperty('--production-opacity', '1')", $script);
    }

    public function test_progressive_scarcity_healthy_inventory(): void
    {
        $show = Show::create([
            'title' => 'عرض صحي التذاكر',
            'description' => 'وصف العرض',
            'is_active' => true,
        ]);

        ShowTime::create([
            'show_id' => $show->id,
            'date' => Carbon::tomorrow()->toDateString(),
            'time' => '19:00:00',
            'ticket_price' => 120,
            'total_tickets' => 100,
            'available_tickets' => 85,
            'is_sold_out' => false,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('متاح للحجز الآن', false);
        $response->assertSee('ticket-state--healthy', false);
        // Verify healthy inventory does not expose raw inventory numbers
        $response->assertDontSee('85 تذكرة', false);
    }

    public function test_progressive_scarcity_low_inventory(): void
    {
        $show = Show::create([
            'title' => 'عرض محدود التذاكر',
            'description' => 'وصف العرض',
            'is_active' => true,
        ]);

        $time = ShowTime::create([
            'show_id' => $show->id,
            'date' => Carbon::tomorrow()->toDateString(),
            'time' => '19:00:00',
            'ticket_price' => 120,
            'total_tickets' => 100,
            'available_tickets' => 20,
            'is_sold_out' => false,
        ]);

        Booking::create([
            'show_time_id' => $time->id,
            'full_name' => 'حجز معلّق',
            'phone' => '01000000000',
            'tickets_count' => 80,
            'total_price' => 9600,
            'reference_code' => 'HOMEPAGE-LOW-1',
            'status' => 'pending',
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('متبقي عدد محدود من التذاكر', false);
        $response->assertSee('ticket-state--low', false);
        $response->assertDontSee('20 تذكرة', false);
    }

    public function test_progressive_scarcity_critical_inventory(): void
    {
        $show = Show::create([
            'title' => 'عرض أوشك على النفاد',
            'description' => 'وصف العرض',
            'is_active' => true,
        ]);

        $time = ShowTime::create([
            'show_id' => $show->id,
            'date' => Carbon::tomorrow()->toDateString(),
            'time' => '19:00:00',
            'ticket_price' => 120,
            'total_tickets' => 100,
            'available_tickets' => 4,
            'is_sold_out' => false,
        ]);

        Booking::create([
            'show_time_id' => $time->id,
            'full_name' => 'حجز معلّق',
            'phone' => '01000000000',
            'tickets_count' => 96,
            'total_price' => 11520,
            'reference_code' => 'HOMEPAGE-CRITICAL-1',
            'status' => 'pending',
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('متبقي 4 تذاكر فقط', false);
        $response->assertSee('ticket-state--critical', false);
    }

    public function test_progressive_scarcity_sold_out(): void
    {
        $show = Show::create([
            'title' => 'عرض مكتمل التذاكر',
            'description' => 'وصف العرض',
            'is_active' => true,
        ]);

        ShowTime::create([
            'show_id' => $show->id,
            'date' => Carbon::tomorrow()->toDateString(),
            'time' => '19:00:00',
            'ticket_price' => 120,
            'total_tickets' => 100,
            'available_tickets' => 0,
            'is_sold_out' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('اكتملت التذاكر', false);
        $response->assertSee('ticket-state--soldout', false);
    }

    public function test_first_stage_playbill_uses_earliest_future_show_and_its_booking_route(): void
    {
        $laterShow = Show::create([
            'title' => 'العرض اللاحق',
            'description' => 'وصف العرض اللاحق',
            'is_active' => true,
        ]);
        $firstShow = Show::create([
            'title' => 'العرض الأقرب',
            'description' => 'وصف العرض الأقرب',
            'poster_path' => 'https://res.cloudinary.com/example/image/upload/v123456/shows/first.jpg',
            'is_active' => true,
        ]);

        ShowTime::create([
            'show_id' => $laterShow->id,
            'date' => Carbon::now()->addDays(5)->toDateString(),
            'time' => '20:00:00',
            'ticket_price' => 100,
            'total_tickets' => 50,
            'available_tickets' => 50,
            'is_sold_out' => false,
        ]);
        $firstTime = ShowTime::create([
            'show_id' => $firstShow->id,
            'date' => Carbon::now()->addDays(2)->toDateString(),
            'time' => '19:30:00',
            'ticket_price' => 150,
            'total_tickets' => 40,
            'available_tickets' => 40,
            'is_sold_out' => false,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('العرض الأقرب', false);
        $response->assertSee('https://res.cloudinary.com/example/image/upload/c_limit,w_960,h_1440,q_auto:good,f_auto,fl_progressive/v123456/shows/first.jpg', false);
        $response->assertSee(route('bookings.create', $firstTime), false);
        $this->assertLessThan(strpos($response->getContent(), 'العرض اللاحق'), strpos($response->getContent(), 'id="featured-title"'));
    }

    public function test_stage_uses_real_booking_reservations_instead_of_cached_available_count(): void
    {
        $show = Show::create([
            'title' => 'العرض المتاح بالحجز الحقيقي',
            'is_active' => true,
        ]);
        $time = ShowTime::create([
            'show_id' => $show->id,
            'date' => Carbon::tomorrow()->toDateString(),
            'time' => '19:00:00',
            'ticket_price' => 120,
            'total_tickets' => 30,
            'available_tickets' => 0,
            'is_sold_out' => false,
        ]);

        Booking::create([
            'show_time_id' => $time->id,
            'full_name' => 'حجز معلّق',
            'phone' => '01000000000',
            'tickets_count' => 8,
            'total_price' => 960,
            'reference_code' => 'HOMEPAGE-PENDING-1',
            'status' => 'pending',
        ]);
        Booking::create([
            'show_time_id' => $time->id,
            'full_name' => 'حجز مرفوض',
            'phone' => '01000000001',
            'tickets_count' => 10,
            'total_price' => 1200,
            'reference_code' => 'HOMEPAGE-REJECTED-1',
            'status' => 'rejected',
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('id="featured-title"', false);
        $response->assertSee(route('bookings.create', $time), false);
        $response->assertSee('متبقي عدد محدود من التذاكر', false);
        $response->assertDontSee('22 تذكرة', false);
    }

    public function test_showtime_selection_excludes_earlier_times_today_and_uses_next_bookable_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-17 18:00:00'));
        $show = Show::create([
            'title' => 'عرض اليوم',
            'is_active' => true,
        ]);
        ShowTime::create([
            'show_id' => $show->id,
            'date' => Carbon::today()->toDateString(),
            'time' => '17:30:00',
            'ticket_price' => 100,
            'total_tickets' => 20,
            'available_tickets' => 20,
            'is_sold_out' => false,
        ]);
        $nextTime = ShowTime::create([
            'show_id' => $show->id,
            'date' => Carbon::today()->toDateString(),
            'time' => '19:00:00',
            'ticket_price' => 100,
            'total_tickets' => 20,
            'available_tickets' => 20,
            'is_sold_out' => false,
        ]);

        $response = $this->get('/');
        Carbon::setTestNow();

        $response->assertOk();
        $response->assertSee(route('bookings.create', $nextTime), false);
        $response->assertDontSee(route('bookings.create', 1), false);
    }

    public function test_booking_stage_is_empty_when_all_future_showtimes_are_closed_or_sold_out(): void
    {
        $show = Show::create([
            'title' => 'عرض مغلق للحجز',
            'is_active' => true,
        ]);
        ShowTime::create([
            'show_id' => $show->id,
            'date' => Carbon::tomorrow()->toDateString(),
            'time' => '19:00:00',
            'ticket_price' => 120,
            'total_tickets' => 20,
            'available_tickets' => 20,
            'is_sold_out' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('id="featured-title"', false);
        $response->assertDontSee(route('bookings.create', 1), false);
        $response->assertSee('خشبة المسرح تستعد للعرض القادم', false);
        $response->assertSee('اكتملت التذاكر', false);
    }

    public function test_missing_production_poster_does_not_reuse_the_team_logo_as_artwork(): void
    {
        $show = Show::create([
            'title' => 'عرض بلا ملصق',
            'is_active' => true,
        ]);
        ShowTime::create([
            'show_id' => $show->id,
            'date' => Carbon::tomorrow()->toDateString(),
            'time' => '19:00:00',
            'ticket_price' => 100,
            'total_tickets' => 20,
            'available_tickets' => 20,
            'is_sold_out' => false,
        ]);

        $response = $this->get('/');
        $html = $response->getContent();
        $posterStateStart = strpos($html, 'class="production-poster-missing"');
        $posterStateEnd = strpos($html, '</div>', $posterStateStart);

        $response->assertOk();
        $response->assertSee('ملصق العرض لم يُنشر بعد', false);
        $this->assertNotFalse($posterStateStart);
        $this->assertStringNotContainsString('sarkha-logo.png', substr($html, $posterStateStart, $posterStateEnd - $posterStateStart));
    }

    public function test_past_performances_do_not_appear_as_upcoming_or_get_fake_countdowns(): void
    {
        $show = Show::create([
            'title' => 'عرض سابق فقط',
            'description' => 'وصف العرض',
            'is_active' => true,
        ]);

        $pastDate = Carbon::now()->subDays(5);
        ShowTime::create([
            'show_id' => $show->id,
            'date' => $pastDate->toDateString(),
            'time' => '20:00:00',
            'ticket_price' => 100,
            'total_tickets' => 50,
            'available_tickets' => 50,
            'is_sold_out' => false,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('data-next-curtain-iso', false);
        $response->assertDontSee('id="featured-title"', false);
        $response->assertSee('لا يوجد موعد قادم', false);
    }
}
