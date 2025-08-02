<?php

declare(strict_types=1);

namespace Modules\Core\app\Logging;

use Illuminate\Support\Facades\Config;
use NewRelic\Monolog\Enricher\Processor;

/**
 * Class NewRelicProcessor
 *
 * @copyright (c) 2024 - HocTran, All Right Reserved.
 * @package Modules\Core\Logging
 */
class NewRelicProcessor extends Processor
{
    /**
     * @param array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        $record['extra']['app_name'] = Config::get('app.name');

        return $record;
    }
}
