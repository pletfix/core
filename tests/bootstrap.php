<?php

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
 * Load the test environment.
 */
Core\Testing\Environment::load();