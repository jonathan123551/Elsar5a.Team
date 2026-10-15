<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Services\TicketRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Support\LogContext;

/**
 * Per-ticket image-build job: QR + composite onto the show template +
 * Cloudinary upload. Dispatched from Admin\BookingController::approve()
 * so the admin's request returns immediately instead of blocking on
 * GD + Cloudinary for every ticket.
 *
 * It intentionally DOES NOT send the WhatsApp image. The actual image
 * send is gated on the customer tapping "استلام التذكرة" (Meta's 24h
 * customer-service window only opens after that inbound message), so
 * delivery is driven by WhatsAppWebhookController -> SendWhatsAppTicketImageJob.
 * This job just makes sure the image is ready and uploaded ahead of the tap.
 *
 * Idempotent on qr_image_path: a retry after a partial failure reuses the
 * existing Cloudinary URL instead of uploading a duplicate.
 */
class GenerateTicketImageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function backoff(): array
    {
        return [5, 15, 60];
    }

    public function __construct(public int $ticketId) {}

    public function handle(TicketRenderer $renderer): void
    {
        $jobRunId = (string) Str::uuid();

        $ticket = Ticket::with(['booking.showTime.show'])->find($this->ticketId);

        if (! $ticket) {
            Log::warning('NON_FATAL_RECOVERY', LogContext::base(
                'NON_FATAL_RECOVERY',
                'jobs.generate_ticket_image.not_found',
                [
                    'job_run_id' => $jobRunId,
                    'ticket_id' => $this->ticketId,
                    'reason' => 'ticket_not_found',
                ]
            ));

            return;
        }

        try {
            $url = $renderer->renderAndUpload($ticket);

            if (! $url) {
                // No ticket template configured on the show yet — same fail-soft
                // behaviour as the original guard in approve(). The send job will
                // try to render again when the customer taps, so nothing is lost.
                Log::info('NON_FATAL_RECOVERY', LogContext::base(
                    'NON_FATAL_RECOVERY',
                    'jobs.generate_ticket_image.no_template',
                    [
                        'job_run_id' => $jobRunId,
                        'ticket_id' => $this->ticketId,
                        'reason' => 'no_ticket_template',
                    ]
                ));

                return;
            }

            Log::info('TICKET_GENERATED', LogContext::base(
                'TICKET_GENERATED',
                'jobs.generate_ticket_image',
                [
                    'job_run_id' => $jobRunId,
                    'ticket_id' => $this->ticketId,
                    'booking_id' => $ticket->booking_id,
                    'ticket_code' => $ticket->ticket_code,
                ]
            ));
        } catch (\Throwable $e) {
            Log::error('TICKET_GENERATION_FAILED', LogContext::base(
                'TICKET_GENERATION_FAILED',
                'jobs.generate_ticket_image',
                [
                    'job_run_id' => $jobRunId,
                    'ticket_id' => $this->ticketId,
                    'exception_class' => get_class($e),
                    'exception_message' => $e->getMessage(),
                ]
            ));

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('TICKET_GENERATION_FAILED', LogContext::base(
            'TICKET_GENERATION_FAILED',
            'jobs.generate_ticket_image.exhausted',
            [
                'ticket_id' => $this->ticketId,
                'exception_class' => get_class($e),
                'exception_message' => $e->getMessage(),
            ]
        ));
    }
}
