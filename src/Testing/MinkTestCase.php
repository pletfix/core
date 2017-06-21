<?php

namespace Core\Testing;

use Behat\Mink\Driver\GoutteDriver;

class MinkTestCase extends TestCase
{
    /**
     * Mink Session
     *
     * @var \Behat\Mink\Session;
     */
    protected $session;

    /**
     * Mink Driver
     *
     * @var \Behat\Mink\Driver\DriverInterface;
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
        $this->session = new \Behat\Mink\Session($this->driver);
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