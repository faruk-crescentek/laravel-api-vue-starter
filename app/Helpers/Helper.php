<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class Helper
{
    /**
     * Generate a random string.
     *
     * @param int $length
     * @return string
     */
    public static function generateRandomString($length = 10)
    {
        return Str::random($length);
    }
}
