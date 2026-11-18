<?php

namespace Webkul\Shop\Support;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class PhoneNumberNormalizer
{
    /**
     * Return a valid phone number in E.164 format, or null when it cannot be parsed.
     */
    public static function normalize(mixed $number, ?string $region = 'MX'): ?string
    {
        if (! is_string($number) && ! is_int($number)) {
            return null;
        }

        $number = trim((string) $number);

        if (preg_match('/[^0-9+().\s-]/u', $number)) {
            return null;
        }

        if ($number === '' || ! $region) {
            return null;
        }

        $number = preg_replace('/[^\d+]/u', '', $number);

        if (str_starts_with($number, '00')) {
            $number = '+'.substr($number, 2);
        }

        $number = preg_replace('/(?!^)\+/', '', $number);

        try {
            $phoneUtil = PhoneNumberUtil::getInstance();
            $parsedNumber = $phoneUtil->parse($number, strtoupper($region));

            if (! $phoneUtil->isValidNumber($parsedNumber)) {
                return null;
            }

            return $phoneUtil->format($parsedNumber, PhoneNumberFormat::E164);
        } catch (NumberParseException) {
            return null;
        }
    }
}
