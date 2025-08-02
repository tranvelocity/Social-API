<?php

declare(strict_types=1);

namespace Modules\Core\app\Logging;

use Illuminate\Log\Logger;
use NewRelic\Monolog\Enricher\Handler;

/**
 * Class NewRelicFormatter
 *
 * @copyright (c) 2024 - HocTran, All Right Reserved.
 * @package Modules\Core\Logging
 */
class NewRelicLogFormatter
{
    /**
     * @param Logger  $logger
     */
    public function __invoke(Logger $logger): void
    {
        $this->addHandler($logger);
        $this->addNewRelicProcessor($logger);
    }

    /**
     * Add Handler.
     * @param Logger $logger
     */
    private function addHandler(Logger $logger): void
    {
        $handler = new Handler();
        $logger->pushHandler($handler);
    }

    /**
     * Add Newrelic Processor.
     * @param Logger $logger
     */
    private function addNewRelicProcessor(Logger $logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            $newRelicProcessor = new NewRelicProcessor();
            $handler->pushProcessor($newRelicProcessor);
        }
    }
}
