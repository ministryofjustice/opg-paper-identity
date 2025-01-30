<?php

declare(strict_types=1);

namespace Application\Helpers;

use DateTime;

class DateProcessorHelper
{
    /**
     * @param string $date Optional,
     * @param string $format Optional. Defaults to "d F Y"
     * @return string
     */
    public static function formatDate(string $date = null, string $format = "d F Y"): string
    {
        if ($date === null) {
            return '';
        }
        return (new DateTime($date))->format($format);
    }
}
