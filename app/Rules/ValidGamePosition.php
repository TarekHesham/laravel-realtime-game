<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidGamePosition implements Rule
{
    public function passes($attribute, $value)
    {
        return is_numeric($value) && $value >= 0 && $value <= 2;
    }

    public function message()
    {
        return 'موقع غير صحيح في اللوحة.';
    }
}
