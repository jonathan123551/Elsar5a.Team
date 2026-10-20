<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Show;
use App\Models\ShowTime;
use App\Models\Ticket;
use App\Models\User;
use App\Support\LogContext;
use Cloudinary\Api\Upload\UploadApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class Phase1InventoryAndLoggingTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::firstOrCreate(
            ['email' => 'elsar5ateam2026@gmail.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password123'),
            ]
        );
    }

    private function createShow(): Show
    {
        return Show::create([
            'title' => 'Elsar5a Play',
            'description' => 'Production test show',
            'ticket_template_path' => 'https://res.cloudinary.com/demo/image/upload/sample.jpg',
            'is_active' => true,
        ]);
    }

    private function createShowTime(Show $show, int $total = 100, int $available = 100): ShowTime
    {
        return ShowTime::create([
            'show_id' => $show->id,
            'date' => '2026-08-01',
            'time' => '20:00:00',
            'ticket_price' => 150,
            'total_tickets' => $total,
            'available_tickets' => $available,
            'is_sold_out' => false,
        ]);
    }

    /**
     * Scenario A:
     * Pending booking with 3 tickets: 100 -> 97 -> reject -> 100
     */
    public function test_pending_reject_restores_inventory_once(): void
    {
        $admin = $this->createAdmin();
        $show = $this->createShow();
        $showTime = $this->createShowTime($show, total: 100, available: 97);

        $booking = Booking::create([
            'show_time_id' => $showTime->id,
            'full_name' => 'John Doe',
            'phone' => '201012345678',
            'tickets_count' => 3,
            'total_price' => 450,
            'status' => 'pending',
            'reference_code' => 'SRC-TEST-PENDING',
        ]);

        for ($i = 1; $i <= 3; $i++) {
            Ticket::create([
                'booking_id' => $booking->id,
                'name' => "Guest {$i}",
                'phone' => '201012345678',
                'ticket_code' => "TIC-P{$i}",
            ]);
        }

        $response = $this->actingAs($admin)
            ->post(route('admin.bookings.reject', $booking));

        $response->assertRedirect();

        $showTime->refresh();
        $booking->refresh();

        $this->assertSame(100, (int) $showTime->available_tickets);
        $this->assertFalse((bool) $showTime->is_sold_out);
        $this->assertSame('rejected', $booking->status);
    }

    /**
     * Scenario B:
     * Reject the same booking again: 100 -> remains 100
     */
    public function test_rejecting_the_same_booking_twice_does_not_restore_twice(): void
    {
        $admin = $this->createAdmin();
        $show = $this->createShow();
        $showTime = $this->createShowTime($show, total: 100, available: 97);

        $booking = Booking::create([
            'show_time_id' => $showTime->id,
            'full_name' => 'John Doe',
            'phone' => '201012345678',
            'tickets_count' => 3,
            'total_price' => 450,
            'status' => 'pending',
            'reference_code' => 'SRC-TEST-REJECT-TWICE',
        ]);

        // First rejection: restores 97 + 3 = 100
        $this->actingAs($admin)
            ->post(route('admin.bookings.reject', $booking))
            ->assertRedirect();

        $showTime->refresh();
        $this->assertSame(100, (int) $showTime->available_tickets);

        // Second rejection: must NOT restore again (100 remains 100)
        $this->actingAs($admin)
            ->post(route('admin.bookings.reject', $booking))
            ->assertRedirect();

        $showTime->refresh();
        $this->assertSame(100, (int) $showTime->available_tickets);
    }

    /**
     * Scenario C:
     * Delete rejected booking: 100 -> remains 100
     */
    public function test_deleting_a_rejected_booking_does_not_restore_again(): void
    {
        $admin = $this->createAdmin();
        $show = $this->createShow();
        $showTime = $this->createShowTime($show, total: 100, available: 97);

        $booking = Booking::create([
            'show_time_id' => $showTime->id,
            'full_name' => 'John Doe',
            'phone' => '201012345678',
            'tickets_count' => 3,
            'total_price' => 450,
            'status' => 'pending',
            'reference_code' => 'SRC-TEST-DELETE-REJECTED',
        ]);

        for ($i = 1; $i <= 3; $i++) {
            Ticket::create([
                'booking_id' => $booking->id,
                'name' => "Guest {$i}",
                'phone' => '201012345678',
                'ticket_code' => "TIC-DEL-R{$i}",
            ]);
        }

        // Reject first: 97 -> 100
        $this->actingAs($admin)
            ->post(route('admin.bookings.reject', $booking))
            ->assertRedirect();

        $showTime->refresh();
        $this->assertSame(100, (int) $showTime->available_tickets);

        // Now delete the rejected booking: must stay 100, not double restore to 103
        $this->actingAs($admin)
            ->delete(route('admin.booking.delete', $booking->id))
            ->assertRedirect();

        $showTime->refresh();
        $this->assertSame(100, (int) $showTime->available_tickets);
        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
        $this->assertDatabaseMissing('tickets', ['booking_id' => $booking->id]);
    }

    /**
     * Scenario D:
     * Approved booking with 4 tickets: 100 -> 96 -> reject -> 100
     */
    public function test_approved_reject_restores_inventory_once(): void
    {
        $admin = $this->createAdmin();
        $show = $this->createShow();
        $showTime = $this->createShowTime($show, total: 100, available: 96);

        $booking = Booking::create([
            'show_time_id' => $showTime->id,
            'full_name' => 'Jane Smith',
            'phone' => '201198765432',
            'tickets_count' => 4,
            'total_price' => 600,
            'status' => 'approved',
            'reference_code' => 'SRC-TEST-APPROVED',
        ]);

        for ($i = 1; $i <= 4; $i++) {
            Ticket::create([
                'booking_id' => $booking->id,
                'name' => "Guest {$i}",
                'phone' => '201198765432',
                'ticket_code' => "TIC-APP{$i}",
            ]);
        }

        $this->actingAs($admin)
            ->post(route('admin.bookings.reject', $booking))
            ->assertRedirect();

        $showTime->refresh();
        $booking->refresh();

        $this->assertSame(100, (int) $showTime->available_tickets);
        $this->assertSame('rejected', $booking->status);
    }

    /**
     * Scenario E:
     * Normal booking creation: 100 -> 97
     */
    public function test_normal_booking_creation_decrements_inventory(): void
    {
        $show = $this->createShow();
        $showTime = $this->createShowTime($show, total: 100, available: 100);

        // Mock Cloudinary upload
        $mockUploadApi = Mockery::mock('overload:' . UploadApi::class);
        $mockUploadApi->shouldReceive('upload')
            ->once()
            ->andReturn([
                'secure_url' => 'https://res.cloudinary.com/demo/image/upload/receipt.jpg',
                'public_id'  => 'payments/screenshots/sample_receipt',
            ]);

        $screenshot = UploadedFile::fake()->create('receipt.jpg', 100, 'image/jpeg');

        $response = $this->post(route('bookings.store', $showTime), [
            'names' => ['Person One', 'Person Two', 'Person Three'],
            'phones' => ['01011111111', '01022222222', '01033333333'],
            'payment_screenshot' => $screenshot,
            'idempotency_token' => 'idem_' . Str::random(16),
        ]);

        $response->assertRedirect();

        $showTime->refresh();
        $this->assertSame(97, (int) $showTime->available_tickets);

        $booking = Booking::where('show_time_id', $showTime->id)->latest()->first();
        $this->assertNotNull($booking);
        $this->assertSame(3, (int) $booking->tickets_count);
        $this->assertSame('pending', $booking->status);
        $this->assertCount(3, $booking->tickets);
    }

    /**
     * Deleting non-rejected booking restores inventory:
     * 100 -> 97 -> delete -> 100
     */
    public function test_deleting_non_rejected_booking_restores_inventory(): void
    {
        $admin = $this->createAdmin();
        $show = $this->createShow();
        $showTime = $this->createShowTime($show, total: 100, available: 97);

        $booking = Booking::create([
            'show_time_id' => $showTime->id,
            'full_name' => 'Alice',
            'phone' => '201011223344',
            'tickets_count' => 3,
            'total_price' => 450,
            'status' => 'pending',
            'reference_code' => 'SRC-DELETE-PENDING',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.booking.delete', $booking->id))
            ->assertRedirect();

        $showTime->refresh();
        $this->assertSame(100, (int) $showTime->available_tickets);
        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
    }

    /**
     * Phone masking tests
     */
    public function test_phone_masking(): void
    {
        $this->assertNull(LogContext::maskPhone(null));
        $this->assertNull(LogContext::maskPhone(''));

        // <= 4 digits
        $this->assertSame('****', LogContext::maskPhone('1234'));

        // <= 8 digits
        $this->assertSame('12****78', LogContext::maskPhone('12345678'));

        // Standard phone number (11 digits): first 4, asterisks, last 4
        $this->assertSame('0101***5678', LogContext::maskPhone('01012345678'));

        // With formatting characters: stripped and masked
        $this->assertSame('2010****5678', LogContext::maskPhone('+20 (10) 1234-5678'));
    }

    /**
     * Logging stack includes stderr even when LOG_STACK=single
     */
    public function test_logging_stack_includes_stderr(): void
    {
        $channels = config('logging.channels.stack.channels');

        $this->assertIsArray($channels);
        $this->assertContains('stderr', $channels);
    }

    /**
     * Correlation ID does not leak in console/queue execution
     */
    public function test_correlation_id_does_not_leak_into_console_or_queue_execution(): void
    {
        // When running in console/queue worker, correlationId must be null
        $this->assertTrue(app()->runningInConsole());
        $this->assertNull(LogContext::correlationId());

        $baseContext = LogContext::base('TEST_EVENT', 'test.op', ['foo' => 'bar']);
        $this->assertSame('TEST_EVENT', $baseContext['event']);
        $this->assertSame('test.op', $baseContext['operation']);
        $this->assertSame('bar', $baseContext['foo']);
        $this->assertArrayNotHasKey('request_id', $baseContext);
    }

    /**
     * whatsappFailure handles null response, non-json responses, and exceptions safely
     */
    public function test_whatsapp_failure_handles_null_response_and_exceptions_safely(): void
    {
        // 1. Null response with exception
        $exception = new \RuntimeException('Connection timed out');
        $failure = LogContext::whatsappFailure('whatsapp.send_test', '01012345678', null, $exception);

        $this->assertSame('WHATSAPP_SEND_FAILED', $failure['event']);
        $this->assertSame('whatsapp.send_test', $failure['operation']);
        $this->assertSame('0101***5678', $failure['phone_masked']);
        $this->assertNull($failure['http_status'] ?? null);
        $this->assertSame('RuntimeException', $failure['exception_class']);
        $this->assertSame('Connection timed out', $failure['exception_message']);

        // 2. Non-2xx response with Meta error JSON
        $metaErrorJson = json_encode([
            'error' => [
                'message' => 'Template does not exist',
                'type' => 'OAuthException',
                'code' => 100,
                'fbtrace_id' => 'xyz',
            ],
        ]);

        $response = new \Illuminate\Http\Client\Response(
            new \GuzzleHttp\Psr7\Response(400, ['Content-Type' => 'application/json'], $metaErrorJson)
        );

        $failureWithResponse = LogContext::whatsappFailure('whatsapp.send_test', '01012345678', $response);
        $this->assertSame(400, $failureWithResponse['http_status']);
        $this->assertSame(100, $failureWithResponse['meta_error_code']);
        $this->assertSame('Template does not exist', $failureWithResponse['meta_error_msg']);
        $this->assertNull($failureWithResponse['exception_class'] ?? null);

        // 3. Ensure no secrets are leaked
        $jsonContext = json_encode($failureWithResponse);
        $this->assertStringNotContainsString('WHATSAPP_TOKEN', $jsonContext);
    }
}
