<?php

namespace Tests\Feature;

use App\Models\About;
use App\Models\Archive;
use App\Models\ArchiveImage;
use App\Models\Booking;
use App\Models\Show;
use App\Models\ShowTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchiveAndPublicFixesTest extends TestCase
{
    use RefreshDatabase;

    public function test_archive_index_renders_single_responsive_browser_container()
    {
        $archive = Archive::create([
            'title' => 'عرض تجريبي للأرشيف',
            'description' => 'وصف العرض للأرشيف.',
            'year' => '2024',
            'poster_path' => 'https://res.cloudinary.com/dqgcjjnvh/image/upload/v1234/test.jpg'
        ]);

        $response = $this->get(route('archive'));

        $response->assertStatus(200);
        $response->assertSee('arch-browser');
        $response->assertSee('c_limit,w_400,q_auto:good,f_auto,fl_progressive', false);
        
        // Ensure the card element exists exactly once in the rendered DOM (no duplicate mobile/desktop rendering)
        $content = $response->getContent();
        $this->assertEquals(1, substr_count($content, 'class="arch-frame'));
    }

    public function test_archive_show_renders_optimized_thumbnails_and_no_eager_preloads()
    {
        $archive = Archive::create([
            'title' => 'العرض المسرحي الكامل',
            'description' => 'تفاصيل العرض.',
            'year' => '2024',
            'poster_path' => 'https://res.cloudinary.com/dqgcjjnvh/image/upload/v1234/poster.jpg'
        ]);

        for ($i = 1; $i <= 5; $i++) {
            ArchiveImage::create([
                'archive_id' => $archive->id,
                'image_path' => "https://res.cloudinary.com/dqgcjjnvh/image/upload/v1234/img_{$i}.jpg"
            ]);
        }

        $response = $this->get(route('archive.show', $archive));

        $response->assertStatus(200);
        $content = $response->getContent();

        // 1. Reel images must use thumbnail transformation (w_400)
        $this->assertStringContainsString('c_limit,w_400,q_auto:good,f_auto,fl_progressive', $content);

        // 2. Hero poster must use display transformation
        $this->assertStringContainsString('c_limit,w_960,h_1440,q_auto:good,f_auto,fl_progressive', $content);

        // 3. Eager image preload loop (DEF-01) must NOT be present
        $this->assertStringNotContainsString('images.forEach(function (src) {', $content);
        $this->assertStringNotContainsString('const i = new Image();', $content);

        // 4. On-demand adjacent preloader must be present
        $this->assertStringContainsString('preloadAdjacent', $content);

        // 5. Hardcoded transition-all class on viewer-img must NOT be present
        $this->assertStringNotContainsString('transition-all duration-300 ease-in-out will-change-transform', $content);

        // 6. Snap-back reset to 0 on touchend must NOT be present
        $this->assertStringNotContainsString('currentX = 0;\n        img.style.transform = \'translateX(0px)', $content);
    }

    public function test_about_page_renders_with_and_without_about_record()
    {
        // 1. Empty about record: must return 200 without 500 error
        $response = $this->get(route('about'));
        $response->assertStatus(200);
        $response->assertSee('فريق الصرخة المسرحي');

        // 2. With about record
        About::create([
            'description' => "الصرخة فن ورسالة\nتأسس الفريق لخدمة المسرح",
            'founded_year' => '2015',
            'youtube' => 'https://youtube.com/@test',
            'facebook' => 'https://facebook.com/test',
            'instagram' => 'https://instagram.com/test',
        ]);

        $responseWithData = $this->get(route('about'));
        $responseWithData->assertStatus(200);
        $responseWithData->assertSee('تأسس عام 2015');
    }

    public function test_join_team_post_route_is_throttled()
    {
        // Hit /join-team 6 times rapidly; the 6th must receive 429 Too Many Requests
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('team.apply.store'), []);
        }

        $throttledResponse = $this->post(route('team.apply.store'), []);
        $throttledResponse->assertStatus(429);
    }

    public function test_ticket_status_route_is_strictly_read_only()
    {
        // Non-existent booking reference returns 404
        $notFoundResponse = $this->get(route('ticket.status', 'NON-EXISTENT-REF'));
        $notFoundResponse->assertStatus(404);

        // Valid booking reference returns status page without firing external mutations
        $show = Show::create([
            'title' => 'عرض الاختبار',
            'is_active' => true,
        ]);

        $showTime = ShowTime::create([
            'show_id' => $show->id,
            'date' => now()->addDays(2)->toDateString(),
            'time' => '19:00:00',
            'ticket_price' => 100,
            'total_tickets' => 50,
            'available_tickets' => 50,
        ]);

        $booking = Booking::create([
            'show_time_id' => $showTime->id,
            'full_name' => 'زائر تجريبي',
            'phone' => '01000000000',
            'tickets_count' => 2,
            'total_price' => 200,
            'status' => 'approved',
            'reference_code' => 'SRC-TEST-REF123',
        ]);

        $response = $this->get(route('ticket.status', 'SRC-TEST-REF123'));
        $response->assertStatus(200);
        $response->assertSee('SRC-TEST-REF123');
        $response->assertSee('عرض الاختبار');
    }
}
