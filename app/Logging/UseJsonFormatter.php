<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Formatter\JsonFormatter;

class UseJsonFormatter
{
    public function __invoke(Logger $logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->setFormatter(new JsonFormatter(
                JsonFormatter::BATCH_MODE_JSON,
                true,
                false,
                true
            ));
        }

        // add datadog related context
        if (function_exists('\DDTrace\logs_correlation_trace_id')) {
            $logger->pushProcessor(function ($record) {
                $record['extra']['dd'] = [
                    'trace_id' => \DDTrace\logs_correlation_trace_id(),
                    'span_id'  => \dd_trace_peek_span_id(),
                ];

                return $record;
            });
        }
    }
}
