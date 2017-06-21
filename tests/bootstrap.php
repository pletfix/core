<?php

/*
 * Save the start time for benchmark tests.
 */
define('APP_STARTTIME', microtime(true));

/*
 * Set the base path for the application.
 */
define('BASE_PATH', realpath(__DIR__ . '/../../../..'));

/*
 * Register the Composer Autoloader
 */
require __DIR__ . '/../../../../vendor/autoload.php';

/*
 * Push the Services into the Dependency Injector.
 */
call_user_func(function() {
    @include __DIR__ . '/../../../../.manifest/plugins/services.php';
    require __DIR__ . '/../../../../config/boot/services.php';
});

/*
 * Bootstrap the framework
 */
call_user_func(function() {
    require __DIR__ . '/../../../../config/boot/bootstrap.php';
    @include __DIR__ . '/../../../../.manifest/plugins/bootstrap.php';
});

Core\Services\DI::getInstance()->get('config')->set('app.env', env('APP_ENV'));