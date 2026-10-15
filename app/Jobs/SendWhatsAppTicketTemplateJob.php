<?php

namespace App\Jobs;

use App\Services\TicketDeliveryService;
use App\Support\LogContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Per-phone WhatsApp template-send job. Replaces the synchronous
 * sendTicketTemplate($phone) loop in Admin\BookingController::approve()
 * that used to dispatch the "ticket" template message to each ticket's
 * phone with a 1-second `sleep()` between calls to dodge Meta rate
 * limits. Single-concurrency queue worker gives us the same pacing
 * with zero in-request wait.
 */
class SendWhatsAppTicketTemplateJob implements ShouldQueue
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

    public function __construct(public string $phone) {}

    public function handle(TicketDeliveryService $delivery): void
    {
        $jobRunId = (string) Str::uuid();
        $response = null;

        try {
            $response = $delivery->sendTemplate($this->phone);

            if ($response->successful()) {
                Log::info(
                    'WHATSAPP_TEMPLATE_SENT',
                    LogContext::base(
                        'WHATSAPP_TEMPLATE_SENT',
                        'whatsapp.send_template',
                        [
                            'job_run_id' => $jobRunId,
                            'phone_masked' => LogContext::maskPhone($this->phone),
                            'http_status' => $response->status(),
                            'external_service' => 'meta_whatsapp',
                        ]
                    )
                );
                return;
            }

            Log::error(
                'WHATSAPP_SEND_FAILED',
                LogContext::whatsappFailure(
                    'whatsapp.send_template',
                    $this->phone,
                    $response,
                    null,
                    [
                        'job_run_id' => $jobRunId,
                    ]
                )
            );

            $errorCode = $response->json('error.code');
            $permanent = in_array($errorCode, [131026, 131047], true);

            if ($permanent) {
                $this->fail(new \RuntimeException(
                    'Permanent Meta error '.$errorCode.' — '.$response->body()
                ));

                return;
            }

            throw new \RuntimeException(
                'WhatsApp template send failed (status '.$response->status().'): '.$response->body()
            );
        } catch (\Throwable $e) {
            if ($response === null) {
                Log::error(
                    'WHATSAPP_SEND_FAILED',
                    LogContext::whatsappFailure(
                        'whatsapp.send_template',
                        $this->phone,
                        null,
                        $e,
                        [
                            'job_run_id' => $jobRunId,
                        ]
                    )
                );
            }

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error(
            'WHATSAPP_SEND_FAILED',
            LogContext::whatsappFailure(
                'whatsapp.send_template.exhausted',
                $this->phone,
                null,
                $e
            )
        );
    }
}
