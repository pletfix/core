<?php

namespace Core;

class Framework
{
    /**
     * The framework version.
     *
     * @var string
     */
    const VERSION = '0.7.2';

    /**
     * Base path of the application.
     *
     * @var string
     */
    protected static $basePath = BASE_PATH;

    /**
     * Get the version number of the framework.
     *
     * @return string
     */
    public static function version()
    {
        return static::VERSION;
    }

    /**
     * Initialize the framework.
     */
    public static function init()
    {
        /*
         * Push the Services into the Dependency Injector.
         */
        call_user_func(function() {
            /** @noinspection PhpIncludeInspection */
            @include self::$basePath . '/.manifest/plugins/services.php';
            /** @noinspection PhpIncludeInspection */
            require self::$basePath . '/config/boot/services.php';
        });

        /*
         * Bootstrap the framework.
         */
        call_user_func(function() {
            /** @noinspection PhpIncludeInspection */
            require self::$basePath . '/config/boot/bootstrap.php';
            /** @noinspection PhpIncludeInspection */
            @include self::$basePath . '/.manifest/plugins/bootstrap.php';
        });
    }
}