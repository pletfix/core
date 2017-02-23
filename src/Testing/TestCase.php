<?php

namespace Core\Testing;

use PHPUnit_Framework_TestCase;

class TestCase extends PHPUnit_Framework_TestCase
{
    /**
     * Setup the test environment.
     *
     * - Load Services
     * - Call Bootstraps
     *
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        /*
         * Push the Services into the Dependency Injector.
         */
        call_user_func(function() {
            require __DIR__ . '/../../../../../config/boot/services.php';
            @include __DIR__ . '/../../../../../.manifest/plugins/services.php';
        });

        /*
         * Bootstrap the framework
         */
        call_user_func(function() {
            require __DIR__ . '/../../../../../config/boot/bootstrap.php';
            @include __DIR__ . '/../../../../../.manifest/plugins/bootstrap.php';
        });
    }
}