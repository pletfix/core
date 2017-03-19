.<?php

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
 *
 * @link https://getcomposer.org/
 */
require __DIR__ . '/../../../../vendor/autoload.php';
