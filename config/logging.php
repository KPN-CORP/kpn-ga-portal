<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    */

    'default' => env('LOG_CHANNEL', 'daily'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    */

    'channels' => [

        /*
        |--------------------------------------------------------------------------
        | Stack
        |--------------------------------------------------------------------------
        */

        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', (string) env('LOG_STACK', 'daily')),
            'ignore_exceptions' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | Single
        |--------------------------------------------------------------------------
        */

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'error'),
            'replace_placeholders' => true,
        ],

        /*
        |--------------------------------------------------------------------------
        | Daily
        |--------------------------------------------------------------------------
        |
        | Log dibuat berdasarkan tanggal dan hanya disimpan 1 hari.
        |
        */

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'error'),
            'days' => 1,
            'replace_placeholders' => true,
        ],

        /*
        |--------------------------------------------------------------------------
        | Slack
        |--------------------------------------------------------------------------
        */

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => env('LOG_SLACK_USERNAME', 'Laravel Log'),
            'emoji' => env('LOG_SLACK_EMOJI', ':boom:'),
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        /*
        |--------------------------------------------------------------------------
        | Papertrail
        |--------------------------------------------------------------------------
        */

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'error'),
            'handler' => env(
                'LOG_PAPERTRAIL_HANDLER',
                SyslogUdpHandler::class
            ),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://' .
                    env('PAPERTRAIL_URL') .
                    ':' .
                    env('PAPERTRAIL_PORT'),
            ],
            'processors' => [
                PsrLogMessageProcessor::class,
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Stderr
        |--------------------------------------------------------------------------
        */

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'error'),
            'handler' => StreamHandler::class,
            'handler_with' => [
                'stream' => 'php://stderr',
            ],
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'processors' => [
                PsrLogMessageProcessor::class,
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Syslog
        |--------------------------------------------------------------------------
        */

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'error'),
            'facility' => env(
                'LOG_SYSLOG_FACILITY',
                LOG_USER
            ),
            'replace_placeholders' => true,
        ],

        /*
        |--------------------------------------------------------------------------
        | Error Log
        |--------------------------------------------------------------------------
        */

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'error'),
            'replace_placeholders' => true,
        ],

        /*
        |--------------------------------------------------------------------------
        | Null
        |--------------------------------------------------------------------------
        */

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        /*
        |--------------------------------------------------------------------------
        | Emergency
        |--------------------------------------------------------------------------
        */

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        /*
        |--------------------------------------------------------------------------
        | SSO Log
        |--------------------------------------------------------------------------
        |
        | Log SSO dibuat harian dan hanya disimpan 1 hari.
        |
        */

        'sso' => [
            'driver' => 'daily',
            'path' => storage_path('logs/sso.log'),
            'level' => 'info',
            'days' => 1,
            'replace_placeholders' => true,
        ],

    ],

];