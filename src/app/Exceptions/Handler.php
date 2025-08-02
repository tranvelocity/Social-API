<?php

namespace App\Exceptions;

use Modules\Core\app\Exceptions\ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * Indicates that an exception instance should only be reported once.
     *
     * @var bool
     */
    protected $withoutDuplicates = true;
}
