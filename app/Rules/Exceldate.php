<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class Exceldate implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        try {
            Date::excelToDateTimeObject($value);
            return true;
        } catch (\ErrorException $e) {
            return false;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be a valid excel date format';
    }
}
