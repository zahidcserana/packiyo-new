<?php

use VCR\VCR;

/**
 * Use VCR to record and replay HTTP requests.
 */
trait RecordsHTTPRequests
{
    // protected static function record(Closure $callback): Response
    protected static function record(
        Closure $callback, array $matches = ['method', 'url', 'query_string', 'host', 'headers']
    ): mixed
    {
        VCR::configure()->setCassettePath('tests/fixtures');
        VCR::configure()->enableLibraryHooks(['curl', 'stream_wrapper']);
        // Not matching body because of incremental IDs.
        VCR::configure()->enableRequestMatchers($matches);
        VCR::turnOn();

        $callingMethodName = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        VCR::insertCassette('crstl-edi-api_' . $callingMethodName . '.yml');

        $response = $callback();

        VCR::eject();
        VCR::turnOff();

        return $response;
    }
}
