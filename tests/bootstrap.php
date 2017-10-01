<?php

/*
 * Save the start time for benchmark tests.
 */
define('APP_STARTTIME', microtime(true));

/*
 * Set the base path of the application.
 */
define('BASE_PATH', realpath(__DIR__ . '/..'));

/*
 * Register the Composer Autoloader.
 */
/** @noinspection PhpIncludeInspection */
require __DIR__ . '/../vendor/autoload.php';

/*
 * Initialize the application.
 */
\Core\Application::init();

/*
 * Override configuration with the environment entry of phpunit.xml.
 */
\Core\Services\DI::getInstance()->get('config')->set('app.env', env('APP_ENV'));