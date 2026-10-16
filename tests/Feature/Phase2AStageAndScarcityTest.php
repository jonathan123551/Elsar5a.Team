<?php

namespace Tests\Feature;

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
        $response->assertSee('images/cinematic/curtain/closed-velvet.webp', false);
        $response->assertSee('images/cinematic/theatre/empty-stage.webp', false);
        $response->assertSee('فريق الصرخة المسرحي', false);
        $response->assertDontSee('فرقة الصرخة المسرحية', false);
        $response->assertSee('كثيرًا ما فسدت عقولنا من ما حملته لها مدخلاتنا, ذلك هو ما نريد تغيره, فقط بالصراخ, الصراخ هنا و هناك.', false);
        $response->assertSee('❤ نجول نصرخ فيزداد العقل وعيا ❤', false);
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
        $response->assertSee('لا يوجد عرض قادم معلن الآن', false);
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

        ShowTime::create([
            'show_id' => $show->id,
            'date' => Carbon::tomorrow()->toDateString(),
            'time' => '19:00:00',
            'ticket_price' => 120,
            'total_tickets' => 100,
            'available_tickets' => 20,
            'is_sold_out' => false,
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

        ShowTime::create([
            'show_id' => $show->id,
            'date' => Carbon::tomorrow()->toDateString(),
            'time' => '19:00:00',
            'ticket_price' => 120,
            'total_tickets' => 100,
            'available_tickets' => 4,
            'is_sold_out' => false,
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
