<?php

namespace App\Support;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;
use Throwable;

class LogContext
{
    /**
     * Returns a correlation ID scoped strictly to the current HTTP request.
     * Returns null in console/queue workers so static state never leaks across jobs.
     */
    public static function correlationId(): ?string
    {
        try {
            if (!app()->runningInConsole() && app()->bound('request')) {
                $req = request();

                if ($req !== null) {
                    if (!$req->attributes->has('_correlation_id')) {
                        $headerId = $req->header('X-Request-Id')
                            ?? $req->header('X-Correlation-Id');

                        $id = (is_string($headerId) && trim($headerId) !== '')
                            ? trim($headerId)
                            : (string) Str::uuid();

                        $req->attributes->set('_correlation_id', $id);
                    }

                    return (string) $req->attributes->get('_correlation_id');
                }
            }
        } catch (Throwable) {
            // Never allow correlation ID resolution to break execution
        }

        return null;
    }

    public static function maskPhone(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return null;
        }

        $digits = preg_replace('/[^0-9]/', '', $phone);
        $len = strlen($digits);

        if ($len === 0) {
            return null;
        }

        if ($len <= 4) {
            return '****';
        }

        if ($len <= 8) {
            return substr($digits, 0, 2)
                . '****'
                . substr($digits, -2);
        }

        return substr($digits, 0, 4)
            . str_repeat('*', $len - 8)
            . substr($digits, -4);
    }

    public static function base(
        string $event,
        string $operation,
        array $extra = []
    ): array {
        $context = array_merge([
            'event' => $event,
            'operation' => $operation,
            'request_id' => self::correlationId(),
        ], $extra);

        return array_filter(
            $context,
            static fn ($v) => $v !== null
        );
    }

    /**
     * Safely formats WhatsApp API failure context whether caused by a non-2xx
     * HTTP response, a malformed non-JSON body, or a thrown ConnectionException.
     */
    public static function whatsappFailure(
        string $operation,
        ?string $phone = null,
        ?Response $response = null,
        ?Throwable $exception = null,
        array $extra = []
    ): array {
        $httpStatus = null;
        $metaErrorCode = null;
        $metaErrorMsg = null;

        if ($response !== null) {
            try {
                $httpStatus = $response->status();

                $decoded = $response->json();

                if (is_array($decoded)) {
                    $metaErrorCode = data_get(
                        $decoded,
                        'error.code'
                    );

                    $metaErrorMsg = data_get(
                        $decoded,
                        'error.message'
                    );
                }
            } catch (Throwable) {
                // Ignore JSON parse errors on non-JSON upstream error responses
            }
        }

        return self::base(
            'WHATSAPP_SEND_FAILED',
            $operation,
            array_merge([
                'external_service' => 'meta_whatsapp',
                'phone_masked' => self::maskPhone($phone),
                'http_status' => $httpStatus,
                'meta_error_code' => $metaErrorCode,
                'meta_error_msg' => $metaErrorMsg,
                'exception_class' => $exception
                    ? get_class($exception)
                    : null,
                'exception_message' => $exception
                    ? $exception->getMessage()
                    : null,
            ], $extra)
        );
    }
}
