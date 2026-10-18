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
        $response->assertDontSee('cinema-skip', false);
        $response->assertDontSee('تخطّي الافتتاحية', false);
        $response->assertDontSee('opening-team-name', false);
        $response->assertSee('Alexandria:wght@700;800;900', false);
        $response->assertSee('Readex+Pro:wght@400;500;600;700', false);
        $response->assertSee('css/elsar5a-stage.css', false);
        $response->assertSee('js/elsar5a-stage.js', false);
        $response->assertSee('class="camera-track has-featured-production"', false);
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
        $response->assertSee('data-opening-phrase="1">لا نريد سوا حواسك', false);
        $response->assertSee('data-opening-phrase="2">نجول', false);
        $response->assertSee('data-opening-phrase="3">نصرخ', false);
        $response->assertSee('data-opening-phrase="4">فيزداد العقل وعيًا', false);
        $homepageHtml = $response->getContent();
        $phrasePositions = array_map(fn ($phrase) => strpos($homepageHtml, $phrase), [
            'data-opening-phrase="1">لا نريد سوا حواسك',
            'data-opening-phrase="2">نجول',
            'data-opening-phrase="3">نصرخ',
            'data-opening-phrase="4">فيزداد العقل وعيًا',
        ]);
        $this->assertSame($phrasePositions, array_values(array_unique($phrasePositions)));
        $sortedPhrasePositions = $phrasePositions;
        sort($sortedPhrasePositions);
        $this->assertSame($sortedPhrasePositions, $phrasePositions);
        $response->assertSee('data-production-step="title"', false);
        $response->assertSee('data-production-step="action"', false);
        $response->assertDontSee('class="upcoming-section"', false);
        $response->assertDontSee('❤ نجول نصرخ فيزداد العقل وعيا ❤', false);
        // The separate manifesto and team-signoff sections have been removed from the homepage
        $response->assertDontSee('class="manifesto-section"', false);
        $response->assertDontSee('id="manifesto-heading"', false);
        $response->assertDontSee('class="team-signoff"', false);
        $response->assertDontSee('كثيرًا ما فسدت عقولنا من ما حملته لها مدخلاتنا', false);
        // Essential slogan lines and footer links are present
        $response->assertSee('class="opening-phrase opening-phrase--final"', false);
        $response->assertDontSee('camera-identity', false);
        $response->assertSee('aria-label="روابط التواصل الاجتماعي"', false);
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
        $response->assertDontSee('class="upcoming-section"', false);
        $response->assertDontSee('لا توجد عروض معلنة في الوقت الحالي.', false);
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
        $response->assertSee('camera-track has-no-featured-production', false);
        $response->assertSee('خشبة المسرح تستعد للعرض القادم', false);
        $response->assertDontSee('عرض غير نشط', false);
    }

    public function test_cinematic_timeline_preserves_reduced_motion_and_releases_the_real_booking_experience(): void
    {
        $css = file_get_contents(public_path('css/elsar5a-stage.css'));
        $script = file_get_contents(public_path('js/elsar5a-stage.js'));

        $this->assertStringContainsString('prefers-reduced-motion: reduce', $css);
        $this->assertStringContainsString('body.cinema-complete .camera-track', $css);
        $this->assertStringContainsString('body.cinema-complete.cinema-static-fallback', $css);
        $this->assertStringContainsString('.no-js .camera-track { position: relative;', $css);
        $this->assertStringContainsString('.cinematic-showtimes', $css);
        $this->assertStringContainsString('background: #000;', $css);
        $this->assertStringContainsString("window.matchMedia('(prefers-reduced-motion: reduce)')", $script);
        $this->assertStringContainsString("production.removeAttribute('inert')", $script);
        $this->assertStringContainsString("track.removeAttribute('inert')", $script);
        $this->assertStringContainsString("addLabel('CURTAIN')", $script);
        $this->assertStringContainsString("addLabel('CAMERA_PUSH')", $script);
        $this->assertStringContainsString("addLabel('PRODUCTION_INFO')", $script);
        $this->assertStringContainsString("addLabel('BLACK')", $script);
        $this->assertStringContainsString("addLabel('WEBSITE_ENVIRONMENT')", $script);
        $this->assertStringContainsString("addLabel('POSTER_REVEAL')", $script);
        $this->assertStringContainsString("addLabel('SHOWTIMES')", $script);
        $this->assertStringContainsString("addLabel('LOGO_HOLD')", $script);
        $this->assertStringContainsString("addLabel('TEXT_SEQUENCE')", $script);
        $this->assertStringNotContainsString("addLabel('EMPTY_STAGE')", $script);
        $this->assertStringContainsString("z: 620, scale: 2.85", $script);
        $this->assertStringContainsString("poster-flight", $css);
        $this->assertStringContainsString("is-flying", $script);
        $this->assertStringContainsString("radial-gradient(circle at 20% 0, rgba(251,191,36,.2)", $css);
        $this->assertStringNotContainsString('skipAndContinue', $script);
        $this->assertStringNotContainsString("addEventListener('scroll'", $script);
        $this->assertStringContainsString("is-settled", $script);
        $this->assertStringContainsString('scale: 2.02, y: 118, z: 134', $script);
        $this->assertStringContainsString("showReducedMotionContent();", $script);
        $this->assertStringContainsString('window.scrollTo(0, 0)', $script);
        $this->assertStringContainsString("curtain.dataset.state = 'open'", $script);
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

    public function test_admin_created_show_with_upcoming_showtime_appears_on_homepage_immediately(): void
    {
        $show = Show::create([
            'title' => 'مسرحية الصرخة الكبرى الأصلية',
            'description' => 'عمل مسرحي جديد ومكتمل الإخراج',
            'is_active' => true,
        ]);

        $time = ShowTime::create([
            'show_id' => $show->id,
            'date' => Carbon::now()->addDays(3)->toDateString(),
            'time' => '19:30:00',
            'ticket_price' => 120,
            'total_tickets' => 80,
            'available_tickets' => 80,
            'is_sold_out' => false,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('مسرحية الصرخة الكبرى الأصلية', false);
        $response->assertSee('id="featured-title"', false);
        $response->assertSee(route('bookings.create', $time), false);
        $response->assertSee('متاح للحجز الآن', false);
    }

    public function test_admin_created_show_without_showtimes_appears_in_upcoming_shows_list(): void
    {
        $show = Show::create([
            'title' => 'عرض مسرحي قيد الإعلان',
            'description' => 'سيتم الإعلان عن المواعيد قريباً',
            'is_active' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('عرض مسرحي قيد الإعلان', false);
        $response->assertSee('لا يوجد موعد قادم', false);
        $response->assertSee(route('shows.show', $show), false);
    }

    public function test_admin_created_show_with_local_storage_poster_resolves_url_correctly(): void
    {
        $show = Show::create([
            'title' => 'عرض بملصق محلي',
            'description' => 'وصف العرض',
            'poster_path' => 'posters/local-sample.jpg',
            'is_active' => true,
        ]);

        $time = ShowTime::create([
            'show_id' => $show->id,
            'date' => Carbon::now()->addDays(2)->toDateString(),
            'time' => '20:00:00',
            'ticket_price' => 100,
            'total_tickets' => 60,
            'available_tickets' => 60,
            'is_sold_out' => false,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('عرض بملصق محلي', false);
        $response->assertSee(asset('storage/posters/local-sample.jpg'), false);
    }

    public function test_show_with_single_showtime_links_directly_to_booking_create(): void
    {
        $show = Show::create([
            'title' => 'عرض بميعاد واحد',
            'description' => 'وصف العرض',
            'is_active' => true,
        ]);

        $time = ShowTime::create([
            'show_id' => $show->id,
            'date' => Carbon::now()->addDays(3)->toDateString(),
            'time' => '19:00:00',
            'ticket_price' => 120,
            'total_tickets' => 50,
            'available_tickets' => 50,
            'is_sold_out' => false,
        ]);

        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee(route('bookings.create', $time), false);
        $response->assertDontSee('data-open-modal="showtime-modal-' . $show->id . '"', false);
    }

    public function test_show_with_multiple_showtimes_renders_selection_modal_with_dates_times_prices(): void
    {
        $show = Show::create([
            'title' => 'عرض بمواعيد متعددة',
            'description' => 'وصف العرض بمواعيد متعددة',
            'is_active' => true,
        ]);

        $time1 = ShowTime::create([
            'show_id' => $show->id,
            'date' => Carbon::now()->addDays(2)->toDateString(),
            'time' => '18:00:00',
            'ticket_price' => 130,
            'total_tickets' => 50,
            'available_tickets' => 50,
            'is_sold_out' => false,
        ]);

        $time2 = ShowTime::create([
            'show_id' => $show->id,
            'date' => Carbon::now()->addDays(4)->toDateString(),
            'time' => '20:30:00',
            'ticket_price' => 160,
            'total_tickets' => 60,
            'available_tickets' => 60,
            'is_sold_out' => false,
        ]);

        $response = $this->get('/');
        $response->assertOk();

        // The card button triggers the modal instead of directly linking to one time
        $response->assertSee('data-open-modal="showtime-modal-' . $show->id . '"', false);
        $response->assertSee('id="showtime-modal-' . $show->id . '"', false);
        $response->assertSee('2 مواعيد متاحة', false);
        $response->assertSee('class="cinematic-showtimes"', false);
        $response->assertSee('data-showtime-card', false);

        // Modal contains both options with their actual date, time, and ticket price
        $response->assertSee($time1->date->format('d/m/Y'), false);
        $response->assertSee('130 ج.م', false);
        $response->assertSee(route('bookings.create', $time1), false);

        $response->assertSee($time2->date->format('d/m/Y'), false);
        $response->assertSee('160 ج.م', false);
        $response->assertSee(route('bookings.create', $time2), false);
    }

    public function test_sold_out_showtime_cannot_be_booked_in_selection_modal(): void
    {
        $show = Show::create([
            'title' => 'عرض بميعاد مكتمل وميعاد متاح',
            'description' => 'وصف العرض',
            'is_active' => true,
        ]);

        $availableTime = ShowTime::create([
            'show_id' => $show->id,
            'date' => Carbon::now()->addDays(3)->toDateString(),
            'time' => '19:00:00',
            'ticket_price' => 140,
            'total_tickets' => 50,
            'available_tickets' => 50,
            'is_sold_out' => false,
        ]);

        $soldOutTime = ShowTime::create([
            'show_id' => $show->id,
            'date' => Carbon::now()->addDays(5)->toDateString(),
            'time' => '21:00:00',
            'ticket_price' => 140,
            'total_tickets' => 50,
            'available_tickets' => 0,
            'is_sold_out' => true,
        ]);

        $response = $this->get('/');
        $response->assertOk();

        // Available slot has a booking link
        $response->assertSee(route('bookings.create', $availableTime), false);

        // Sold-out slot must NOT have an active booking link
        $response->assertDontSee(route('bookings.create', $soldOutTime), false);
    }

    public function test_homepage_ending_renders_simplified_footer_with_logo_slogan_and_social_links(): void
    {
        \App\Models\About::create([
            'description' => 'وصف الفريق',
            'founded_year' => '2019',
            'youtube' => 'https://youtube.com/@test-sarkha',
            'facebook' => 'https://facebook.com/test-sarkha',
            'instagram' => 'https://instagram.com/test-sarkha',
        ]);

        $response = $this->get('/');
        $response->assertOk();

        // Footer must contain team logo, team name, and short slogan
        $response->assertSee('images/sarkha-logo.png', false);
        $response->assertSee('فريق الصرخة المسرحي', false);
        $response->assertSee('نجول نصرخ فيزداد العقل وعياً', false);

        // Footer must contain configured social links
        $response->assertSee('https://youtube.com/@test-sarkha', false);
        $response->assertSee('https://facebook.com/test-sarkha', false);
        $response->assertSee('https://instagram.com/test-sarkha', false);

        // Oversized separate team-signoff card must NOT exist
        $response->assertDontSee('class="team-signoff"', false);
    }
}
