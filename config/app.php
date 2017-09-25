<?php

return [

    /**
     * ----------------------------------------------------------------
     * Application Name
     * ----------------------------------------------------------------
     *
     * This value is the name of your application.
     */

    'name' => 'Pletfix Application',

    /**
     * ----------------------------------------------------------------
     * Application Version
     * ----------------------------------------------------------------
     *
     * Defines the Code Version.
     */

    'version' => '0.7.2',

    /**
     * ----------------------------------------------------------------
     * Application URL
     * ----------------------------------------------------------------
     *
     * Here you may specify the canonical URL of the application.
     */

    'url' => env('APP_URL', 'http://localhost'),

    /**
     * ----------------------------------------------------------------
     * Environment
     * ----------------------------------------------------------------
     *
     * This value determines the "environment" your application is currently
     * running in.
     *
     * Available Settings: "local", "staging", production", "testing"
     */

    'env' => env('APP_ENV', 'production'),

    /**
     * ----------------------------------------------------------------
     * Debug Mode
     * ----------------------------------------------------------------
     *
     * When your application is in debug mode, detailed error messages with
     * stack traces will be shown on every error that occurs within your
     * application. If disabled, a simple generic error page is shown.
     */

    'debug' => env('APP_DEBUG', false),

    /**
     * ----------------------------------------------------------------
     * HTTP Proxy
     * ----------------------------------------------------------------
     *
     * The HTTP proxy to tunnel requests through, e.g. 172.31.1.234:8080
     */

    'http_proxy' => env('HTTP_PROXY'),

];