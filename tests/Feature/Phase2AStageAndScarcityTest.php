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
        $response->assertSee('id="elsar5a-curtain-overlay"', false);
        $response->assertSee('data-curtain-enabled="true"', false);
        $response->assertSee('id="elsar5a-curtain-skip"', false);
        $response->assertSee('تخطي الافتتاحية', false);
        $response->assertSee('Alexandria:wght@700;800;900', false);
        $response->assertSee('Readex+Pro:wght@400;500;600;700', false);
        $response->assertSee('css/elsar5a-stage.css', false);
        $response->assertSee('js/elsar5a-stage.js', false);
        $response->assertSee('id="elsar5a-stage-world"', false);
        $response->assertSee('class="stage-light-rig"', false);
        $response->assertSee('فرقة الصرخة المسرحية', false);
        $response->assertSee('عروض مسرحية حية تجمع بين', false);
        $response->assertSee('مسرح يصنع الأثر، وصوت ينبض بالحياة', false);
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
        $response->assertDontSee('id="elsar5a-curtain-overlay"', false);
        $response->assertDontSee('id="elsar5a-curtain-skip"', false);
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
        $response->assertSee('scarcity-badge--healthy', false);
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
        $response->assertSee('scarcity-badge--low', false);
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
        $response->assertSee('scarcity-badge--critical', false);
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
        $response->assertSee('scarcity-badge--soldout', false);
    }

    public function test_curtain_countdown_renders_only_when_future_showtime_exists(): void
    {
        $show = Show::create([
            'title' => 'عرض مستقبلي',
            'description' => 'وصف العرض',
            'is_active' => true,
        ]);

        $futureDate = Carbon::now()->addDays(3);
        ShowTime::create([
            'show_id' => $show->id,
            'date' => $futureDate->toDateString(),
            'time' => '20:00:00',
            'ticket_price' => 100,
            'total_tickets' => 50,
            'available_tickets' => 50,
            'is_sold_out' => false,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('data-next-curtain-iso', false);
        $response->assertSee('يرفع الستار للعرض القادم خلال', false);
        $response->assertSee('data-cd-days', false);
        $response->assertSee('data-cd-hours', false);
        $response->assertSee('data-cd-mins', false);
    }

    public function test_curtain_countdown_does_not_render_when_no_future_showtimes(): void
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
        $response->assertDontSee('يرفع الستار للعرض القادم خلال', false);
    }
}
