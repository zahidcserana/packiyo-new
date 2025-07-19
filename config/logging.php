<?php

use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

$stackChannels = ['daily'];

if (env('LOG_SLACK_WEBHOOK_URL')) {
    $stackChannels[] = 'slack';
}

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => $stackChannels,
        ],

        'single' => [
            'driver' => 'single',
            'tap' => [App\Logging\UseJsonFormatter::class],
            'path' => storage_path('logs/laravel.log'),
            'level' => 'debug',
        ],

        'daily' => [
            'driver' => 'daily',
            'tap' => [App\Logging\UseJsonFormatter::class],
            'path' => storage_path('logs/laravel.log'),
            'level' => 'debug',
            'days' => env('LOG_DAYS', 14),
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => 'error',
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => 'debug',
            'handler' => SyslogUdpHandler::class,
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
            ],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => 'debug',
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => 'debug',
        ],

        'bulkshipping' => [
            'driver' => 'daily',
            'tap' => [App\Logging\UseJsonFormatter::class],
            'path' => storage_path('logs/bulkshipping.log'),
            'level' => 'info',
            'days' => env('LOG_DAYS', 14),
        ],

        'api' => [
            'driver' => 'daily',
            'tap' => [App\Logging\UseJsonFormatter::class],
            'path' => storage_path('logs/api.log'),
            'level' => 'debug',
            'days' => env('LOG_DAYS', 14),
        ],

        'webhooks' => [
            'driver' => 'daily',
            'tap' => [App\Logging\UseJsonFormatter::class],
            'path' => storage_path('logs/webhooks.log'),
            'level' => 'debug',
            'days' => env('LOG_DAYS', 14),
        ],

        'billing' => [
            'driver' => 'daily',
            'tap' => [App\Logging\UseJsonFormatter::class],
            'path' => storage_path('logs/billing.log'),
            'level' => 'debug',
            'days' => env('LOG_DAYS', 14),
        ],

        'picking' => [
            'driver' => 'daily',
            'tap' => [App\Logging\UseJsonFormatter::class],
            'path' => storage_path('logs/picking.log'),
            'level' => 'debug',
            'days' => env('LOG_DAYS', 14),
        ]
    ],

];
