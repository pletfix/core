<?php

namespace Core\Testing;

use Core\Services\DI;
use PHPUnit_Framework_Assert as PHPUnit;
use PHPUnit_Framework_TestCase;

class TestCase extends PHPUnit_Framework_TestCase
{
    /**
     * Assert whether the client was redirected to a given URL.
     *
     * @param string $url
     */
    public function assertRedirectedTo($url)
    {
        $response = DI::getInstance()->get('response');
        PHPUnit::assertTrue(in_array($response->getStatusCode(), [301, 302, 303]));
        PHPUnit::assertEquals($url, $response->getHeader('location'));
    }

    /**
     * Setup the test environment.
     *
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
    }

    /**
     * Tears down the fixture.
     *
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }
}