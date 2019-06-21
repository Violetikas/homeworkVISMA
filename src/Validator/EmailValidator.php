<?php

namespace App\Validator;

class EmailValidator
{
    public function isValid(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }
}
