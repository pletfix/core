<?php

return [

    /**
     * ----------------------------------------------------------------
     * Type
     * ----------------------------------------------------------------
     *
     * Can be set to "single", "daily", "syslog" or "errorlog".
     */
    'type' => env('LOG_TYPE',  'daily'),

    /**
     * ----------------------------------------------------------------
     * Level
     * ----------------------------------------------------------------
     *
     * The minimum PSR-3 logging level at which this handler will be triggered.
     * Can be set to "debug", "info", "notice", "warning", "error", "critical", "alert" or "emergency".
     */
    'level' => env('LOG_LEVEL', 'debug'),

    /**
     * ----------------------------------------------------------------
     * Maximum amount of files
     * ----------------------------------------------------------------
     *
     * Only for daily log: Maximum files to use in the daily logging format (0 means unlimited).
     */
    'max_files' => 5,

    /**
     * ----------------------------------------------------------------
     * Filename of the application log.
     * ----------------------------------------------------------------
     */
    'app_file' => 'app.log',

    /**
     * ----------------------------------------------------------------
     * Filename of the console log.
     * ----------------------------------------------------------------
     */
    'cli_file' => 'cli.log',

    /**
     * ----------------------------------------------------------------
     * File permission
     * ----------------------------------------------------------------
     *
     * Optional file permissions (null == 0644 are only for owner read/write).
     */
    'permission' => 0664,
];