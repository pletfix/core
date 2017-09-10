<?php

return [

    /**
     * ----------------------------------------------------------------
     * Application Name
     * ----------------------------------------------------------------
     *
     * This value is the name of your application. This value is used when the
     * framework needs to place the application's name in a notification or
     * any other location as required by the application or its packages.
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
     * running in. This may determine how you prefer to configure various
     * services your application utilizes. Set this in your ".env" file.
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
     * Default Timezone
     * ----------------------------------------------------------------
     *
     * Here you may specify the default timezone for your application, which
     * will be used by the PHP date and date-time functions. We have gone
     * ahead and set this to a sensible default for you out of the box.
     */

    'timezone' => 'Europe/Berlin',

    /**
     * ----------------------------------------------------------------
     * First day of the week.
     * ----------------------------------------------------------------
     *
     * According to international standard ISO 8601, Monday is the first day
     * of the week. Yet several countries, including the United States and
     * Canada, consider Sunday as the start of the week.
     *
     * 0 (for Sunday) through 6 (for Saturday)
     */

    'first_dow' => 1, // Monday

    /**
     * ----------------------------------------------------------------
     * Locale
     * ----------------------------------------------------------------
     *
     * The application locale determines the default locale that will be used
     * by the translation service provider. You are free to set this value
     * to any of the locales which will be supported by the application.
     */

    'locale' => 'de',

    /**
     * ----------------------------------------------------------------
     * Fallback Locale
     * ----------------------------------------------------------------
     *
     * The fallback locale determines the locale to use when the current one
     * is not available. You may change the value to correspond to any of
     * the language folders that are provided through your application.
     */

    'fallback_locale' => 'en',

];