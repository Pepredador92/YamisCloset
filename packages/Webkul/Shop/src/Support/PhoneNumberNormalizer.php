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
    public static function normalize(?string $number, ?string $region): ?string
    {
        $number = trim((string) $number);

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
