<?php

namespace App\Validator;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;

/**
 * Validates phone numbers using libphonenumber for PHP.
 */
class PhoneValidator
{
    public function isValid(string $value): bool
    {
        $phoneUtil = PhoneNumberUtil::getInstance();
        try {
            $swissNumberProto = $phoneUtil->parse($value, 'LT');
        } catch (NumberParseException $e) {
            return false;
        }

        return $phoneUtil->isValidNumber($swissNumberProto);
    }
}
