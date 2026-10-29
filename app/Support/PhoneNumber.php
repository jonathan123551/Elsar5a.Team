<?php

namespace App\Support;

/**
 * Canonical WhatsApp identity for Egyptian phone numbers.
 *
 * Mirrors the normalization the WhatsApp webhook has always used for
 * CTA correlation, so equivalent inputs compare equal:
 *   01222356357 / 1222356357 / 201222356357 / +201222356357 / 00201222356357
 *   → 201222356357
 */
final class PhoneNumber
{
    public static function canonical($value): string
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $value);

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            $digits = '2' . $digits;
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '1')) {
            $digits = '20' . $digits;
        }

        return $digits;
    }

    public static function same($a, $b): bool
    {
        $ca = self::canonical($a);

        return $ca !== '' && $ca === self::canonical($b);
    }
}
