<?php

return [

    /**
     * ----------------------------------------------------------------
     * Supported Locales
     * ----------------------------------------------------------------
     *
     * List of Languages that your site supports.
     *
     * Use the two-letter language code as key and the native name as value
     * according to [ISO 639-1](https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes).
     *
     * Note that if you define more than one language here, the function
     * `canonical_url()` prefixes the URL with the current language code.
     */

    'supported' => [
        'en' => 'English',
        'de' => 'Deutsch',
    ],

    /**
     * ----------------------------------------------------------------
     * Default Locale
     * ----------------------------------------------------------------
     *
     * The locale that will be used by default.
     */

    'default' => 'de',

    /**
     * ----------------------------------------------------------------
     * Fallback Locale
     * ----------------------------------------------------------------
     *
     * The fallback locale is used when the current one is not available.
     */

    'fallback' => 'en',

    /**
     * ----------------------------------------------------------------
     * Default Timezone
     * ----------------------------------------------------------------
     *
     * Here you may specify the default timezone for your application, which
     * will be used by the PHP date and date-time functions.
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

];