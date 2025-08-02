<?php

declare(strict_types=1);

namespace Modules\Core\app\Traits;

trait WithDataFormatters
{
    public function formatDateTime($date)
    {
        return ($date == null || is_string($date)) ? $date : $date->format('Y-m-d H:i:s');
    }
}
