<?php

namespace Core\Testing;

use Behat\Mink\Driver\DriverInterface;
use Behat\Mink\Driver\GoutteDriver;
use Behat\Mink\Session;

class MinkTestCase extends TestCase
{
    /**
     * Mink Session
     *
     * @var Session;
     */
    protected $session;

    /**
     * Mink Driver
     *
     * @var DriverInterface;
     */
    protected $driver;

    /**
     * Setup the test environment.
     *
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->driver = new GoutteDriver();
        $this->session = new Session($this->driver);
        $this->session->start();
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        $this->session->stop();
    }
}