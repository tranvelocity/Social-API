<?php

declare(strict_types=1);

namespace Modules\Core\app\Logging;

use Illuminate\Log\Logger;
use Modules\Core\Logging\GlobalTracer;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\WebProcessor;

/**
 * Class DataDogLogFormatter
 *
 * @copyright (c) 2024 - HocTran, All Right Reserved.
 * @package Modules\Core\Logging
 */

class DataDogLogFormatter
{
    /**
     * @param Logger $logger
     */
    public function __invoke(Logger $logger): void
    {
        $this->addWebProcessor($logger);
        $this->addIntrospectionProcessor($logger);
        $this->addTraceIdProcessor($logger);
    }

    /**
     * Add Web Processor.
     * @param Logger $logger
     */
    private function addWebProcessor(Logger $logger): void
    {
        $webProcessor = new WebProcessor();
        foreach ($logger->getHandlers() as $handler) {
            $handler->pushProcessor($webProcessor);
        }
    }

    /**
     * Add Introspection Processor.
     * @param Logger $logger
     */
    private function addIntrospectionProcessor(Logger $logger): void
    {
        $introspectionProcessor = new IntrospectionProcessor();
        foreach ($logger->getHandlers() as $handler) {
            $handler->pushProcessor($introspectionProcessor);
        }
    }

    /**
     * Add TraceId Processor.
     * @param Logger $logger
     */
    private function addTraceIdProcessor(Logger $logger): void
    {
        $shouldAddTraceId = class_exists(GlobalTracer::class);
        if (!$shouldAddTraceId) {
            return;
        }

        $traceIdProcessor = function ($record) {
            $span = GlobalTracer::get()->getActiveSpan();
            if (!is_null($span)) {
                $record['dd.trace_id'] = $span->getTraceId();
            }
            return $record;
        };

        foreach ($logger->getHandlers() as $handler) {
            $handler->pushProcessor($traceIdProcessor);
        }
    }
}
